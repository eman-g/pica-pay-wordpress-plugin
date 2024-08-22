const { registerPlugin } = wp.plugins;

const { __ } = wp.i18n;
const { compose } = wp.compose;
const { withSelect, withDispatch } = wp.data;

const { PluginDocumentSettingPanel } = wp.editPost;
const { ToggleControl, TextControl, PanelRow } = wp.components;

const PP_BlockEditor_Meta = ( { postType, postMeta, setPostMeta } ) => {

	if (!postMeta) return null;

	const picaPayPaidValue = postMeta._pica_pay_paid !== undefined ? postMeta._pica_pay_paid : false;
	// Use the option default if _pica_pay_charge is not set
	const picaPayChargeValue = (postMeta._pica_pay_charge !== undefined && postMeta._pica_pay_charge !== 0)
		? postMeta._pica_pay_charge
		: window?.picaPayData?.defaultCharge || 0;

	return(
		<PluginDocumentSettingPanel title={ __( 'Pica-Pay', 'txtdomain') } icon="edit" initialOpen="true">
			<PanelRow>
				<ToggleControl
					label={ __( 'Paid content', 'txtdomain' ) }
					onChange={(value) => setPostMeta({ ...postMeta, _pica_pay_paid: value })}
					checked={ picaPayPaidValue }
				/>
			</PanelRow>
			<PanelRow>
				<TextControl
					label={ __( 'Charge (in cents)', 'txtdomain' ) }
					onChange={(value) => {
						setPostMeta({ ...postMeta, _pica_pay_charge: value });
					}}
					value={ picaPayChargeValue }
				/>
			</PanelRow>
		</PluginDocumentSettingPanel>
	);
}

const enhance = compose([
	withSelect( ( select ) => {
		return {
			postMeta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
			postType: select( 'core/editor' ).getCurrentPostType(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		return {
			setPostMeta( newMeta ) {
				dispatch( 'core/editor' ).editPost( { meta: newMeta } );
			}
		};
	} )
]);

registerPlugin('pp-custom-postmeta-plugin', {
	render: enhance(PP_BlockEditor_Meta),
});
