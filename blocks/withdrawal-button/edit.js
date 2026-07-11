/**
 * Editor UI for the withdrawal button block.
 *
 * @package EUWithdrawal
 */

import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { label } = attributes;
	const blockProps = useBlockProps( {
		className: 'eu-withdrawal-block-editor',
	} );

	const buttonLabel =
		label ||
		__( 'Request withdrawal', 'eu-withdrawal-for-woocommerce' );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Settings',
						'eu-withdrawal-for-woocommerce'
					) }
				>
					<TextControl
						label={ __(
							'Button label',
							'eu-withdrawal-for-woocommerce'
						) }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
						help={ __(
							'Text displayed on the withdrawal trigger button.',
							'eu-withdrawal-for-woocommerce'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<button
					type="button"
					className="eu-withdrawal-block-editor__preview button"
					disabled
				>
					{ buttonLabel }
				</button>
				<p className="eu-withdrawal-block-editor__notice">
					{ __(
						'Formularul de retragere va fi afișat aici pe frontend.',
						'eu-withdrawal-for-woocommerce'
					) }
				</p>
			</div>
		</>
	);
}
