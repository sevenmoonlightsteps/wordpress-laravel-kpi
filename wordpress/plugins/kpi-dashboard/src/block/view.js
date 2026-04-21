( function () {
	const ROOT_ID = 'kpi-dashboard-root';
	const SUMMARY_URL = '/wp-json/kpi-dashboard/v1/summary';
	const REFRESH_INTERVAL_MS = 60_000;
	const SPARKLINE_POINTS = 5;
	const CHART_COLOR = '#1a73e8';
	const PREFIX_UNITS = new Set( [ '$', '€' ] );

	const root = document.getElementById( ROOT_ID );
	if ( ! root ) return;

	const nonce = root.dataset.nonce || '';

	const chartInstances = new Map();
	let lastFetchTime = null;
	let footerEl = null;
	let tickInterval = null;

	function formatValue( value, unit ) {
		const formatted = Number( value ).toLocaleString();
		if ( PREFIX_UNITS.has( unit ) ) {
			return unit + formatted;
		}
		return formatted + ( unit ? ' ' + unit : '' );
	}

	function createLoadingEl() {
		const wrapper = document.createElement( 'div' );
		wrapper.className = 'kpi-dashboard kpi-dashboard--loading';
		for ( let i = 0; i < 3; i++ ) {
			const card = document.createElement( 'div' );
			card.className = 'kpi-card kpi-card--skeleton';
			wrapper.appendChild( card );
		}
		return wrapper;
	}

	function createErrorEl() {
		const wrapper = document.createElement( 'div' );
		wrapper.className = 'kpi-dashboard kpi-dashboard--error';
		const msg = document.createElement( 'p' );
		msg.className = 'kpi-dashboard__error-msg';
		msg.textContent = 'Could not load KPI data. Please try again later.';
		wrapper.appendChild( msg );
		return wrapper;
	}

	function mountEl( el ) {
		root.replaceChildren( el );
	}

	function destroyAllCharts() {
		chartInstances.forEach( ( chart ) => chart.destroy() );
		chartInstances.clear();
	}

	function buildSparkline( canvas, value ) {
		const flatData = Array( SPARKLINE_POINTS ).fill( value );
		return new window.Chart( canvas, {
			type: 'line',
			data: {
				labels: flatData.map( () => '' ),
				datasets: [
					{
						data: flatData,
						borderColor: CHART_COLOR,
						borderWidth: 2,
						pointRadius: 0,
						tension: 0,
						fill: false,
					},
				],
			},
			options: {
				animation: false,
				responsive: false,
				plugins: {
					legend: { display: false },
					tooltip: { enabled: false },
				},
				scales: {
					x: { display: false },
					y: { display: false },
				},
			},
		} );
	}

	function renderCards( items ) {
		destroyAllCharts();

		const dashboard = document.createElement( 'div' );
		dashboard.className = 'kpi-dashboard';

		items.forEach( ( kpi ) => {
			if ( kpi.id == null || kpi.name == null || kpi.latest_value == null ) return;

			const card = document.createElement( 'div' );
			card.className = 'kpi-card';
			card.dataset.kpiId = kpi.id;

			const header = document.createElement( 'div' );
			header.className = 'kpi-card__header';

			const nameEl = document.createElement( 'span' );
			nameEl.className = 'kpi-card__name';
			nameEl.textContent = kpi.name;

			const unitEl = document.createElement( 'span' );
			unitEl.className = 'kpi-card__unit';
			unitEl.textContent = kpi.unit || '';

			header.appendChild( nameEl );
			header.appendChild( unitEl );

			const valueEl = document.createElement( 'div' );
			valueEl.className = 'kpi-card__value';
			valueEl.textContent = formatValue( kpi.latest_value, kpi.unit );

			const canvas = document.createElement( 'canvas' );
			canvas.className = 'kpi-card__chart';
			canvas.width = 200;
			canvas.height = 60;

			card.appendChild( header );
			card.appendChild( valueEl );
			card.appendChild( canvas );
			dashboard.appendChild( card );

			chartInstances.set( kpi.id, buildSparkline( canvas, kpi.latest_value ) );
		} );

		const footer = document.createElement( 'div' );
		footer.className = 'kpi-dashboard__footer';

		const updatedEl = document.createElement( 'span' );
		updatedEl.className = 'kpi-dashboard__updated';
		updatedEl.textContent = 'Updated just now';

		footer.appendChild( updatedEl );
		dashboard.appendChild( footer );

		mountEl( dashboard );

		footerEl = updatedEl;
		lastFetchTime = Date.now();
	}

	function updateFooterText() {
		if ( ! footerEl || lastFetchTime === null ) return;
		const elapsed = Math.floor( ( Date.now() - lastFetchTime ) / 1000 );
		footerEl.textContent = elapsed < 10 ? 'Updated just now' : `Updated ${ elapsed } seconds ago`;
	}

	async function fetchAndRender() {
		try {
			const response = await fetch( SUMMARY_URL, {
				headers: {
					'X-WP-Nonce': nonce,
					Accept: 'application/json',
				},
			} );

			if ( ! response.ok ) {
				mountEl( createErrorEl() );
				return;
			}

			const body = await response.json();

			if ( ! body.success || ! Array.isArray( body.data ) ) {
				mountEl( createErrorEl() );
				return;
			}

			renderCards( body.data );
		} catch ( _err ) {
			mountEl( createErrorEl() );
		}
	}

	function startFooterTick() {
		if ( tickInterval ) clearInterval( tickInterval );
		tickInterval = setInterval( updateFooterText, 5_000 );
	}

	mountEl( createLoadingEl() );

	fetchAndRender().then( startFooterTick );

	setInterval( () => fetchAndRender().then( startFooterTick ), REFRESH_INTERVAL_MS );
} )();
