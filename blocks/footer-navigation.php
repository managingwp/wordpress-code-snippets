<?php
/**
 * Plugin Name: Footer Navigation Block
 * Description: A block that renders a WordPress menu as an unordered list.
 * Version: 1.0.0
 * Author: Jordan
 * Author URI: https://managingwp.io/
 * Type: plugin
 * Status: Complete
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register the block
add_action('init', function() {
    register_block_type('custom/footer-navigation', array(
        'api_version' => 2,
        'title' => 'Footer Navigation',
        'category' => 'widgets',
        'icon' => 'menu',
        'description' => 'Display a WordPress menu as an unordered list.',
        'supports' => array(
            'html' => false,
            'align' => true,
            'anchor' => true,
            'customClassName' => true,
            'color' => array(
                'background' => true,
                'text' => true,
                'link' => true,
            ),
            'spacing' => array(
                'margin' => true,
                'padding' => true,
            ),
            'typography' => array(
                'fontSize' => true,
                'lineHeight' => true,
            ),
        ),
        'attributes' => array(
            'menuId' => array(
                'type' => 'string',
                'default' => '',
            ),
            'menuClass' => array(
                'type' => 'string',
                'default' => 'footer-menu',
            ),
            'listStyle' => array(
                'type' => 'string',
                'default' => 'none',
            ),
        ),
        'editor_script' => 'footer-navigation-block-editor',
        'render_callback' => 'render_footer_navigation_block',
    ));
});

// Render callback function
function render_footer_navigation_block($attributes, $content) {
    $menu_id = isset($attributes['menuId']) ? intval($attributes['menuId']) : 0;
    $menu_class = isset($attributes['menuClass']) ? esc_attr($attributes['menuClass']) : 'footer-menu';
    $list_style = isset($attributes['listStyle']) ? esc_attr($attributes['listStyle']) : 'none';
    
    if (!$menu_id) {
        return '<div class="wp-block-custom-footer-navigation"><p>Please select a menu in the block settings.</p></div>';
    }
    
    $menu_items = wp_get_nav_menu_items($menu_id);
    
    if (!$menu_items || is_wp_error($menu_items)) {
        return '<div class="wp-block-custom-footer-navigation"><p>Menu not found or empty.</p></div>';
    }
    
    // Build hierarchical menu structure
    $menu_tree = array();
    $menu_items_by_id = array();
    
    foreach ($menu_items as $item) {
        $menu_items_by_id[$item->ID] = $item;
        $item->children = array();
    }
    
    foreach ($menu_items as $item) {
        if ($item->menu_item_parent == 0) {
            $menu_tree[] = $item;
        } else {
            if (isset($menu_items_by_id[$item->menu_item_parent])) {
                $menu_items_by_id[$item->menu_item_parent]->children[] = $item;
            }
        }
    }
    
    // Generate unique class for this menu instance
    $unique_class = 'footer-nav-' . uniqid();
    
    ob_start();
    ?>
    <style>
        .<?php echo $unique_class; ?> ul {
            list-style-type: <?php echo $list_style; ?>;
            <?php if ($list_style === 'none'): ?>
            margin: 0;
            padding: 0;
            <?php endif; ?>
        }
    </style>
    <nav class="wp-block-custom-footer-navigation <?php echo $unique_class; ?>">
        <?php echo render_menu_tree($menu_tree, $menu_class); ?>
    </nav>
    <?php
    return ob_get_clean();
}

// Helper function to recursively render menu tree
function render_menu_tree($items, $class = '') {
    if (empty($items)) {
        return '';
    }
    
    $output = '<ul class="' . esc_attr($class) . '">';
    
    foreach ($items as $item) {
        $classes = !empty($item->classes) ? implode(' ', $item->classes) : '';
        $output .= '<li class="' . esc_attr($classes) . '">';
        $output .= '<a href="' . esc_url($item->url) . '"';
        
        if (!empty($item->target)) {
            $output .= ' target="' . esc_attr($item->target) . '"';
        }
        
        if (!empty($item->xfn)) {
            $output .= ' rel="' . esc_attr($item->xfn) . '"';
        }
        
        $output .= '>' . esc_html($item->title) . '</a>';
        
        if (!empty($item->children)) {
            $output .= render_menu_tree($item->children, $class . '-submenu');
        }
        
        $output .= '</li>';
    }
    
    $output .= '</ul>';
    
    return $output;
}

// Add REST API endpoint to fetch menu items
add_action('rest_api_init', function() {
    register_rest_route('custom/v1', '/menu/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_menu_items_rest',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
});

function get_menu_items_rest($request) {
    $menu_id = intval($request['id']);
    $menu_items = wp_get_nav_menu_items($menu_id);
    
    if (!$menu_items || is_wp_error($menu_items)) {
        return new WP_Error('no_menu', 'Menu not found', array('status' => 404));
    }
    
    $items = array();
    foreach ($menu_items as $item) {
        $items[] = array(
            'id' => intval($item->ID),
            'title' => $item->title,
            'url' => $item->url,
            'parent' => intval($item->menu_item_parent),
            'classes' => !empty($item->classes) ? $item->classes : array(),
            'target' => $item->target,
        );
    }
    
    return rest_ensure_response($items);
}

// Register editor script
add_action('enqueue_block_editor_assets', function() {
    // Get all registered menus
    $menus = wp_get_nav_menus();
    $menu_options = array();
    
    foreach ($menus as $menu) {
        $menu_options[] = array(
            'label' => $menu->name,
            'value' => strval($menu->term_id),
        );
    }
    
    wp_register_script(
        'footer-navigation-block-editor',
        false,
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components')
    );
    
    // Pass menu options to JavaScript
    wp_localize_script('footer-navigation-block-editor', 'footerNavMenus', array(
        'options' => $menu_options
    ));
    
    wp_add_inline_script('footer-navigation-block-editor', "
        (function(blocks, element, blockEditor, components, data) {
            const el = element.createElement;
            const { useState, useEffect } = element;
            const useBlockProps = blockEditor.useBlockProps;
            const InspectorControls = blockEditor.InspectorControls;
            const PanelBody = components.PanelBody;
            const SelectControl = components.SelectControl;
            const TextControl = components.TextControl;
            const Spinner = components.Spinner;
            
            // Helper to build hierarchical menu structure
            function buildMenuTree(items) {
                const itemsById = {};
                const tree = [];
                
                items.forEach(item => {
                    itemsById[item.id] = { ...item, children: [] };
                });
                
                items.forEach(item => {
                    if (item.parent == 0) {
                        tree.push(itemsById[item.id]);
                    } else if (itemsById[item.parent]) {
                        itemsById[item.parent].children.push(itemsById[item.id]);
                    }
                });
                
                return tree;
            }
            
            // Helper to render menu items recursively
            function renderMenuItems(items, className, listStyle) {
                if (!items || items.length === 0) return null;
                
                return el('ul', { 
                    className: className,
                    style: { listStyleType: listStyle }
                },
                    items.map(item => {
                        const itemClasses = item.classes ? item.classes.join(' ') : '';
                        return el('li', { key: item.id, className: itemClasses },
                            el('a', { 
                                href: item.url,
                                onClick: (e) => e.preventDefault(),
                                style: { cursor: 'default' }
                            }, item.title),
                            item.children && item.children.length > 0 
                                ? renderMenuItems(item.children, className + '-submenu', listStyle)
                                : null
                        );
                    })
                );
            }
            
            blocks.registerBlockType('custom/footer-navigation', {
                title: 'Footer Navigation',
                icon: 'menu',
                category: 'widgets',
                supports: {
                    align: true,
                    anchor: true,
                    customClassName: true,
                    color: {
                        background: true,
                        text: true,
                        link: true
                    },
                    spacing: {
                        margin: true,
                        padding: true
                    },
                    typography: {
                        fontSize: true,
                        lineHeight: true
                    }
                },
                attributes: {
                    menuId: {
                        type: 'string',
                        default: ''
                    },
                    menuClass: {
                        type: 'string',
                        default: 'footer-menu'
                    },
                    listStyle: {
                        type: 'string',
                        default: 'none'
                    }
                },
                edit: function(props) {
                    const { attributes, setAttributes } = props;
                    const { menuId, menuClass, listStyle } = attributes;
                    
                    const [menuItems, setMenuItems] = useState(null);
                    const [isLoading, setIsLoading] = useState(false);
                    
                    const blockProps = useBlockProps();
                    
                    // Fetch menu items when menuId changes
                    useEffect(() => {
                        if (menuId) {
                            setIsLoading(true);
                            
                            const fetchOptions = {
                                method: 'GET',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': wpApiSettings.nonce
                                }
                            };
                            
                            fetch(wpApiSettings.root + 'custom/v1/menu/' + menuId, fetchOptions)
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('HTTP error ' + response.status);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    console.log('Menu data received:', data);
                                    if (data && Array.isArray(data) && data.length > 0) {
                                        setMenuItems(data);
                                    } else if (data && data.code) {
                                        console.error('API Error:', data);
                                        setMenuItems([]);
                                    } else {
                                        console.log('Empty menu data');
                                        setMenuItems([]);
                                    }
                                    setIsLoading(false);
                                })
                                .catch(error => {
                                    console.error('Error fetching menu:', error);
                                    setMenuItems([]);
                                    setIsLoading(false);
                                });
                        } else {
                            setMenuItems(null);
                        }
                    }, [menuId]);
                    
                    // Prepare menu options for SelectControl
                    const menuOptions = [
                        { label: 'Select a menu...', value: '' },
                        ...(footerNavMenus.options || [])
                    ];
                    
                    // Find selected menu name
                    const selectedMenu = footerNavMenus.options.find(m => m.value === menuId);
                    const selectedMenuName = selectedMenu ? selectedMenu.label : 'No menu selected';
                    
                    // Render menu preview
                    let menuPreview;
                    if (!menuId) {
                        menuPreview = el('p', { 
                            style: { 
                                padding: '20px',
                                textAlign: 'center',
                                color: '#666',
                                backgroundColor: '#f9f9f9',
                                border: '1px solid #e0e0e0',
                                borderRadius: '4px'
                            }
                        }, 'Please select a menu from the sidebar →');
                    } else if (isLoading) {
                        menuPreview = el('div', { 
                            style: { 
                                padding: '40px',
                                textAlign: 'center',
                                backgroundColor: '#f9f9f9',
                                border: '1px solid #e0e0e0',
                                borderRadius: '4px'
                            }
                        }, el(Spinner));
                    } else if (menuItems && menuItems.length > 0) {
                        const menuTree = buildMenuTree(menuItems);
                        console.log('Menu tree built:', menuTree);
                        console.log('Menu items count:', menuItems.length);
                        console.log('Tree items count:', menuTree.length);
                        menuPreview = el('nav', { 
                            className: 'wp-block-custom-footer-navigation'
                        }, renderMenuItems(menuTree, menuClass, listStyle));
                    } else {
                        console.log('Menu items state:', menuItems);
                        menuPreview = el('p', { 
                            style: { 
                                padding: '20px',
                                textAlign: 'center',
                                color: '#999',
                                backgroundColor: '#f9f9f9',
                                border: '1px solid #e0e0e0',
                                borderRadius: '4px'
                            }
                        }, 'Menu is empty or not found');
                    }
                    
                    return el('div', {},
                        el(InspectorControls, {},
                            el(PanelBody, { title: 'Menu Settings', initialOpen: true },
                                el(SelectControl, {
                                    label: 'Select Menu',
                                    value: menuId,
                                    options: menuOptions,
                                    onChange: function(value) {
                                        setAttributes({ menuId: value });
                                    },
                                    help: 'Choose which WordPress menu to display'
                                }),
                                el(TextControl, {
                                    label: 'Menu CSS Class',
                                    value: menuClass,
                                    onChange: function(value) {
                                        setAttributes({ menuClass: value });
                                    },
                                    help: 'CSS class for the menu <ul> element'
                                }),
                                el(SelectControl, {
                                    label: 'List Style',
                                    value: listStyle,
                                    options: [
                                        { label: 'None', value: 'none' },
                                        { label: 'Disc', value: 'disc' },
                                        { label: 'Circle', value: 'circle' },
                                        { label: 'Square', value: 'square' },
                                        { label: 'Decimal', value: 'decimal' },
                                        { label: 'Lower Alpha', value: 'lower-alpha' },
                                        { label: 'Upper Alpha', value: 'upper-alpha' },
                                        { label: 'Lower Roman', value: 'lower-roman' },
                                        { label: 'Upper Roman', value: 'upper-roman' }
                                    ],
                                    onChange: function(value) {
                                        setAttributes({ listStyle: value });
                                    },
                                    help: 'Choose the list style type'
                                })
                            )
                        ),
                        el('div', blockProps, menuPreview)
                    );
                },
                save: function() {
                    return null; // Dynamic block, rendered in PHP
                }
            });
        })(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data);
    ");
});
