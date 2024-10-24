<?php
/**
 * Plugin Name: Ultimo Custom Domain Edits
 * Description: Custom Domain Edits for Ultimo
 * Version: 1.0
 * Author: LMT Solutions 
 * Author URI: https://lmt.ca
 * License: GPL2
 * Status: Complete
 */
#wu_custom_domain_after
function custom_domain_text () {  
    $server_addr = WU_Settings::get_setting('network_ip');
    # Note: In order to set a Custom Domain Name you must have purchased and pointed your custom domain name already. 
    # Do not use this for changing a sub domain name! To do that contact info@company.com and request a sub domain name change.
    $custom_domain = wu_get_current_site()->get_meta('custom-domain');
    $server_ips = array('192.168.0.1','192.168.0.2');

    if ($custom_domain) {    
      $main_domain = dns_get_record($custom_domain, DNS_A + DNS_CNAME);
      $main_domain_type = $main_domain[0]['type'];
      $www_domain = dns_get_record("www.".$custom_domain, DNS_A + DNS_CNAME);
      $www_domain_type = $www_domain[0]['type'];
      # echo "<pre>".print_r($main_domain)."</pre>";

      echo "<div class=\"custom-domain-status\">Custom Domain Status</div>";
      echo "<div class=\"custom-domain-check\"><b>$custom_domain</b> ";
      if ($main_domain_type == 'CNAME') {
        echo "is type CNAME pointed to ".$main_domain[0]['target']."</div>";
        $main_domain_status = "true";
      } elseif ($main_domain_type == 'A' ) {
        echo "is type A pointed to ".$main_domain[0]['ip']."</div>";
        $main_domain_status = "true";
      } else {
        echo "<b>Failed to resolve domain</b></div>";
      }
      
      echo "<div class=\"custom-domain-check\"><b>www.$custom_domain</b> ";
      if ($www_domain_type == 'CNAME') {
        echo "is type CNAME pointed to ".$www_domain[0]['target']."</div>";
        $www_domain_status = "true";
      } elseif ($www_domain_type == 'A' ) {
        echo "is type A pointed to ".$www_domain[0]['ip']."</div>";
        $www_domain_status = "true";
      } else {
        echo "<b>Failed to resolve domain</b></div>";
      }
      
      # Check root domain is pointed and present success or error message.

      $main_domain_ip = $main_domain[0]['ip'];
      if ($main_domain[0]['ip'] == $server_addr) {
          echo "<div class=\"custom-domain-success\">SUCCESS: $custom_domain is pointed properly!</div>";
      } elseif ( in_array ( $main_domain_ip, $server_ips )) {
          echo "<div class=\"custom-domain-warning\">WARNING: $custom_domain is pointed at Cloudflare, can't detect if working!</div>";
      } elseif ($main_domain[0]['ip'] != $server_addr) {
          echo "<div class=\"custom-domain-error\">ERROR: $custom_domain is not pointed to $server_addr, please check your DNS!</div>";
      } else {
          echo "<div class=\"custom-domain-error\">ERROR: something went wrong.</div>";
      }

      $www_domain_ip = $www_domain[0]['ip'];
      if ( $www_domain[0]['ip'] == $server_addr ) {
          echo "<div class=\"custom-domain-success\">SUCCESS: www.$custom_domain is pointed properly!</div>";
      } elseif ( in_array ( $www_domain_ip, $server_ips ) ) {
          echo "<div class=\"custom-domain-warning\">WARNING: www.$custom_domain is pointed at Cloudflare, can't detect if working!</div>";
      } elseif ( $www_domain[0]['ip'] != $server_addr ) {
          echo "<div class=\"custom-domain-error\">ERROR: www.$custom_domain is not pointed to $server_addr</div>";
      } else {
          echo "<div class=\"custom-domain-error\">ERROR: something went wrong.</div>";
      }
      
      echo "<br><br>";
      echo "<div class=\"custom-domain-status\">Support</div>";
      echo "<div class=\"custom-domain-text\">Note: In order to set a Custom Domain Name you must have purchased and pointed your custom domain name already.";
      echo "<br><br>Do not use this for changing a sub domain name! To do that contact info@company.com and request a sub domain name change.";
      echo "<br><br>For more information on to use this feature visit.</div>";
      echo "<div class=\"custom-domain-link\"><a href=\"https://domain.com\" target=\"_blank\">Company Support</a></div>";
      
    }
}

add_action( 'wu_custom_domain_after', 'custom_domain_text', 10, 2 );

function custom_domain_text_css () {
  echo '<style>
   .custom-domain-step {
    font-size: 15px!important;
    color: black!important;
   }
   .custom-domain-step p {
   font-size: 14px!important;
    color: black!important;
   }
   .custom-domain-status {
        font-size: 16px !important;
        color: black !important;
        background: #ddd;
        text-align: center;
        font-weight:bold !important;
  }
  .custom-domain-success {
        font-size: 14px !important;
        color: black !important;
        background: lightgreen;
        text-align: center;
        font-weight:bold !important;  
  }
  .custom-domain-warning {
        font-size: 14px !important;
        color: black !important;
        background: yellow;
        text-align: center;
        font-weight:bold !important;
  } 
  .custom-domain-error {
        font-size: 14px !important;
        color: black !important;
        background: red;
        text-align: center;
        font-weight:bold !important;
  }
  .custom-domain-check {
        font-size: 14px !important;
        color: black !important;
        background: #white;
        text-align:center;
  }
  .custom-domain-text {
        font-size: 14px!important;
        color: black!important;
        font-weight: bold;
        text-align: center;
    }
    .custom-domain-link {
        font-size: 16px!important;
        color: black!important;
        background-color: white;
        font-weight: bold;
        text-align: center;
    }
  </style>';
}

add_action('admin_head', 'custom_domain_text_css');

/*function update_ultimo_point () {
"Point an A Record to the following IP Address <code>%s</code>." */

function change_custom_domain_text ( $translated_text, $text, $domain ) {
  if( $translated_text == 'You can use a custom domain with your website.') {
    $server_addr = WU_Settings::get_setting('network_ip');
    $translated_text = "";
    echo "<div class=\"custom-domain-status\">Custom Domain Instructions</div>";
    echo "<div class=\"custom-domain-step\" style=\"color:red!important;\"><b><center>Caution: This is not for changing your sub-domain .</center></b></div>";
    echo "<br>";
    echo "<div class=\"custom-domain-step\"><b>Step 1</b> - Register your domain name at <a href=\"https://namecheap.com/\">Namecheap</a></div>";
    echo "<br>";
    echo "<div class=\"custom-domain-step\"><b>Step 2</b> - Change your domain names DNS to the following:";
    echo "<p>Create an <b>A</b> Record for <b>@</b> to point to <b>$server_addr</b>";
    echo "<br>Create an <b>A</b> Record for <b>www</b> to point to <b>$server_addr</b></div>";
    echo "<div class=\"custom-domain-step\"><b>Step 3</b> - Enter in your domain name and extension (yoursite.com) below. Do not enter in www in the front of your domain.</div>";
  }

  if( $translated_text == 'Point an A Record to the following IP Address <code>%s</code>.') {
    $translated_text = "";
  }
  if( $translated_text == 'You can also create a CNAME record on your domain pointing to our domain <code>%s</code>.') {
    $translated_text = "";
  }
  return $translated_text;
}
add_filter( 'gettext', 'change_custom_domain_text', 20, 3 );
