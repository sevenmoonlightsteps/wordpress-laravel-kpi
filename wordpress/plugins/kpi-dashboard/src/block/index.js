( function( blocks, element ) {
	var el = element.createElement;

	blocks.registerBlockType( 'kpi-dashboard/kpi-dashboard', {
		title: 'KPI Dashboard',
		icon: 'chart-line',
		category: 'widgets',
		edit: function() {
			return el( 'div', { className: 'kpi-dashboard-editor-placeholder' },
				'KPI Dashboard — configure in Settings → KPI Dashboard'
			);
		},
		save: function() {
			return el( 'div', { id: 'kpi-dashboard-root' } );
		},
	} );
} )( window.wp.blocks, window.wp.element );
