import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => (
		<div className="kpi-dashboard-editor-placeholder">
			KPI Dashboard &mdash; configure in Settings &rarr; KPI Dashboard
		</div>
	),
	save: () => <div id="kpi-dashboard-root"></div>,
} );
