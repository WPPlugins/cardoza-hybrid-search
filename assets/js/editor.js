/**
 * Cardoza Hybrid Search — block editor panel.
 *
 * Adds a "Cardoza Hybrid Search" panel to the post sidebar with a switch that sets
 * the _cardoza_hs_exclude post meta. The value is saved with the post over REST.
 * Plain JS (no build step), using WordPress's bundled packages.
 */
( function ( wp ) {
	'use strict';

	const el                         = wp.element.createElement;
	const __                         = wp.i18n.__;
	const { useSelect, useDispatch } = wp.data;
	const { ToggleControl }          = wp.components;
	// wp.editor in WordPress 6.6+; wp.editPost before that.
	const PluginDocumentSettingPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	const META_KEY                   = '_cardoza_hs_exclude';

	if ( ! PluginDocumentSettingPanel ) {
		return;
	}

	function ExcludePanel() {
		const meta         = useSelect(
			function ( select ) {
				return select( 'core/editor' ).getEditedPostAttribute( 'meta' );
			},
			[]
		);
		const { editPost } = useDispatch( 'core/editor' );

		// Meta is missing when the post type doesn't expose it over REST.
		if ( ! meta || ! Object.prototype.hasOwnProperty.call( meta, META_KEY ) ) {
			return null;
		}

		const excluded = ! ! meta[ META_KEY ];

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'cardoza-hs-exclude',
				title: __( 'Cardoza Hybrid Search', 'cardoza-hybrid-search' ),
			},
			el(
				ToggleControl,
				{
					label: __( 'Exclude from AI search', 'cardoza-hybrid-search' ),
					help: excluded
					? __( 'This post will not be indexed or returned in AI search results.', 'cardoza-hybrid-search' )
					: __( 'This post is indexed when published.', 'cardoza-hybrid-search' ),
					checked: excluded,
					onChange: function ( value ) {
						editPost( { meta: { [ META_KEY ]: value } } );
					},
					__nextHasNoMarginBottom: true,
				}
			)
		);
	}

	wp.plugins.registerPlugin(
		'cardoza-hs-exclude',
		{
			render: ExcludePanel,
			icon: 'search',
		}
	);
}( window.wp ) );
