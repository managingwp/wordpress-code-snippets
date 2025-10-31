<?php
/**
 * Plugin Name: Email Subscribe Block
 * Description: A simple form that redirects with email query param.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register the block
add_action('init', function() {
    register_block_type('custom/email-redirect', array(
        'api_version' => 2,
        'title' => 'Email Redirect Form',
        'category' => 'widgets',
        'icon' => 'email',
        'description' => 'A simple form that redirects with email query param.',
        'supports' => array(
            'html' => false,
            'align' => true,
            'anchor' => true,
            'customClassName' => true,
        ),
        'attributes' => array(
            'formClass' => array(
                'type' => 'string',
                'default' => '',
            ),
            'buttonClass' => array(
                'type' => 'string',
                'default' => '',
            ),
            'inputPlaceholder' => array(
                'type' => 'string',
                'default' => 'Enter your email...',
            ),
            'redirectUrl' => array(
                'type' => 'string',
                'default' => '/sign-up/',
            ),
            'buttonBorderWidth' => array(
                'type' => 'string',
                'default' => '1px',
            ),
            'buttonBorderColor' => array(
                'type' => 'string',
                'default' => '#999',
            ),
            'buttonBorderRadius' => array(
                'type' => 'string',
                'default' => '4px',
            ),
            'buttonBackgroundColor' => array(
                'type' => 'string',
                'default' => '#e0e0e0',
            ),
            'buttonBackgroundColorHover' => array(
                'type' => 'string',
                'default' => '#d0d0d0',
            ),
        ),
        'editor_script' => 'email-redirect-block-editor',
        'render_callback' => 'render_email_redirect_block',
    ));
});

// Render callback function
function render_email_redirect_block($attributes, $content) {
    // Generate unique ID to avoid conflicts if multiple forms on page
    $form_id = 'redirectForm-' . uniqid();
    
    // Get custom classes from attributes
    $form_class = isset($attributes['formClass']) ? esc_attr($attributes['formClass']) : '';
    $button_class = isset($attributes['buttonClass']) ? esc_attr($attributes['buttonClass']) : '';
    $input_placeholder = isset($attributes['inputPlaceholder']) ? esc_attr($attributes['inputPlaceholder']) : 'Enter your email...';
    $redirect_url = isset($attributes['redirectUrl']) ? esc_url($attributes['redirectUrl']) : '/sign-up/';
    
    // Get button styling attributes
    $button_border_width = isset($attributes['buttonBorderWidth']) ? esc_attr($attributes['buttonBorderWidth']) : '1px';
    $button_border_color = isset($attributes['buttonBorderColor']) ? esc_attr($attributes['buttonBorderColor']) : '#999';
    $button_border_radius = isset($attributes['buttonBorderRadius']) ? esc_attr($attributes['buttonBorderRadius']) : '4px';
    $button_bg_color = isset($attributes['buttonBackgroundColor']) ? esc_attr($attributes['buttonBackgroundColor']) : '#e0e0e0';
    $button_bg_color_hover = isset($attributes['buttonBackgroundColorHover']) ? esc_attr($attributes['buttonBackgroundColorHover']) : '#d0d0d0';
    
    // Generate unique class for this button instance
    $button_unique_class = 'btn-' . uniqid();
    
    ob_start(); ?>
    <style>
        .<?php echo $button_unique_class; ?> {
            border: <?php echo $button_border_width; ?> solid <?php echo $button_border_color; ?>;
            border-radius: <?php echo $button_border_radius; ?>;
            background-color: <?php echo $button_bg_color; ?>;
            transition: background-color 0.3s ease;
        }
        .<?php echo $button_unique_class; ?>:hover {
            background-color: <?php echo $button_bg_color_hover; ?>;
        }

        /* --- Responsive stacking --- */
        @media (max-width: 800px) {
            #<?php echo esc_attr($form_id); ?> {
                flex-direction: column;
                align-items: stretch !important;
            }

            #<?php echo esc_attr($form_id); ?> input[type="email"],
            #<?php echo esc_attr($form_id); ?> button {
                width: 100%;
            }

            #<?php echo esc_attr($form_id); ?> button {
                margin-top: 8px;
            }
        }
    </style>
    <div class="wp-block-custom-email-redirect">
        <form id="<?php echo esc_attr($form_id); ?>" class="email-redirect-form <?php echo $form_class; ?>" style="display: flex; gap: 8px; align-items: center; padding: 16px;">            
            <input type="email" id="<?php echo esc_attr($form_id); ?>-email" name="email" required style="flex: 1;" placeholder="<?php echo $input_placeholder; ?>">
            <button type="submit" class="<?php echo $button_class; ?> <?php echo $button_unique_class; ?>">Sign Up</button>
        </form>
    </div>

    <script>
    (function() {
        const form = document.getElementById('<?php echo esc_js($form_id); ?>');
        if(form){
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const emailInput = document.getElementById('<?php echo esc_js($form_id); ?>-email');
                const email = encodeURIComponent(emailInput.value);
                const baseUrl = '<?php echo esc_js($redirect_url); ?>';
                // Add email parameter, handling existing query strings
                const separator = baseUrl.includes('?') ? '&' : '?';
                const targetUrl = baseUrl + separator + 'email=' + email;
                window.location.href = targetUrl;
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

// Register editor script
add_action('enqueue_block_editor_assets', function() {
    wp_register_script(
        'email-redirect-block-editor',
        false,
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components')
    );
    
    wp_add_inline_script('email-redirect-block-editor', "
        (function(blocks, element, blockEditor, components) {
            const el = element.createElement;
            const useBlockProps = blockEditor.useBlockProps;
            const InspectorControls = blockEditor.InspectorControls;
            const PanelBody = components.PanelBody;
            const TextControl = components.TextControl;
            const ColorPalette = components.ColorPalette;
            const PanelRow = components.PanelRow;
            
            blocks.registerBlockType('custom/email-redirect', {
                title: 'Email Redirect Form',
                icon: 'email',
                category: 'widgets',
                supports: {
                    align: true,
                    anchor: true,
                    customClassName: true,
                },
                attributes: {
                    formClass: {
                        type: 'string',
                        default: ''
                    },
                    buttonClass: {
                        type: 'string',
                        default: ''
                    },
                    inputPlaceholder: {
                        type: 'string',
                        default: 'Enter your email...'
                    },
                    redirectUrl: {
                        type: 'string',
                        default: '/sign-up/'
                    },
                    buttonBorderWidth: {
                        type: 'string',
                        default: '1px'
                    },
                    buttonBorderColor: {
                        type: 'string',
                        default: '#999'
                    },
                    buttonBorderRadius: {
                        type: 'string',
                        default: '4px'
                    },
                    buttonBackgroundColor: {
                        type: 'string',
                        default: '#e0e0e0'
                    },
                    buttonBackgroundColorHover: {
                        type: 'string',
                        default: '#d0d0d0'
                    }
                },
                edit: function(props) {
                    const { attributes, setAttributes } = props;
                    const { formClass, buttonClass, inputPlaceholder, redirectUrl, buttonBorderWidth, buttonBorderColor, buttonBorderRadius, buttonBackgroundColor, buttonBackgroundColorHover } = attributes;
                    
                    const blockProps = useBlockProps();
                    
                    return el('div', {},
                        el(InspectorControls, {},
                            el(PanelBody, { title: 'Form Settings', initialOpen: true },
                                el(TextControl, {
                                    label: 'Redirect URL',
                                    value: redirectUrl,
                                    onChange: function(value) {
                                        setAttributes({ redirectUrl: value });
                                    },
                                    help: 'URL to redirect to after form submission. The email will be appended as ?email={email}'
                                }),
                                el(TextControl, {
                                    label: 'Input Placeholder Text',
                                    value: inputPlaceholder,
                                    onChange: function(value) {
                                        setAttributes({ inputPlaceholder: value });
                                    },
                                    help: 'Placeholder text shown inside the email input'
                                })
                            ),
                            el(PanelBody, { title: 'Button Styling', initialOpen: false },
                                el(TextControl, {
                                    label: 'Border Width',
                                    value: buttonBorderWidth,
                                    onChange: function(value) {
                                        setAttributes({ buttonBorderWidth: value });
                                    },
                                    help: 'e.g., 1px, 2px, 3px'
                                }),
                                el(PanelRow, {},
                                    el('div', { style: { width: '100%' } },
                                        el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '600' } }, 'Border Color'),
                                        el(TextControl, {
                                            value: buttonBorderColor,
                                            onChange: function(value) {
                                                setAttributes({ buttonBorderColor: value });
                                            },
                                            help: 'Hex color code (e.g., #999)'
                                        })
                                    )
                                ),
                                el(TextControl, {
                                    label: 'Border Radius',
                                    value: buttonBorderRadius,
                                    onChange: function(value) {
                                        setAttributes({ buttonBorderRadius: value });
                                    },
                                    help: 'e.g., 4px, 8px, 50%'
                                }),
                                el(PanelRow, {},
                                    el('div', { style: { width: '100%' } },
                                        el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '600' } }, 'Background Color'),
                                        el(TextControl, {
                                            value: buttonBackgroundColor,
                                            onChange: function(value) {
                                                setAttributes({ buttonBackgroundColor: value });
                                            },
                                            help: 'Hex color code (e.g., #e0e0e0)'
                                        })
                                    )
                                ),
                                el(PanelRow, {},
                                    el('div', { style: { width: '100%' } },
                                        el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '600' } }, 'Background Color (Hover)'),
                                        el(TextControl, {
                                            value: buttonBackgroundColorHover,
                                            onChange: function(value) {
                                                setAttributes({ buttonBackgroundColorHover: value });
                                            },
                                            help: 'Hex color code (e.g., #d0d0d0)'
                                        })
                                    )
                                )
                            ),
                            el(PanelBody, { title: 'CSS Classes', initialOpen: false },
                                el(TextControl, {
                                    label: 'Form CSS Class',
                                    value: formClass,
                                    onChange: function(value) {
                                        setAttributes({ formClass: value });
                                    },
                                    help: 'Add custom CSS class to the form element'
                                }),
                                el(TextControl, {
                                    label: 'Button CSS Class',
                                    value: buttonClass,
                                    onChange: function(value) {
                                        setAttributes({ buttonClass: value });
                                    },
                                    help: 'Add custom CSS class to the button element'
                                })
                            )
                        ),
                        el('div', blockProps, 
                            el('form', { 
                                style: { 
                                    display: 'flex', 
                                    gap: '8px', 
                                    alignItems: 'center',
                                    margin: 0,
                                    padding: '16px'
                                },
                                onSubmit: function(e) { e.preventDefault(); }
                            },
                                el('input', { 
                                    type: 'email', 
                                    placeholder: inputPlaceholder || 'Enter your email...', 
                                    disabled: true,
                                    style: { 
                                        flex: '1',
                                        padding: '8px',
                                        color: '#333',
                                        border: '1px solid #ccc',
                                        borderRadius: '4px'
                                    }
                                }),
                                el('button', { 
                                    disabled: true,
                                    style: { 
                                        padding: '8px 16px', 
                                        color: '#333', 
                                        backgroundColor: buttonBackgroundColor,
                                        border: buttonBorderWidth + ' solid ' + buttonBorderColor,
                                        borderRadius: buttonBorderRadius,
                                        cursor: 'pointer',
                                        whiteSpace: 'nowrap'
                                    }
                                }, 'Sign Up')
                            )
                        )
                    );
                },
                save: function() {
                    return null; // Dynamic block, rendered in PHP
                }
            });
        })(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
    ");
});
