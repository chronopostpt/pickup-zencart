<?php
/**
 * @package shippingMethod
 * @copyright Copyright 2012 AmplitudeNet
 * @copyright Portions 2003-2009 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
  */
/**
 * Store-Pickup / Will-Call shipping method
 *
 */
class chronopostpickme extends base {
  /**
   * $code determines the internal 'code' name used to designate "this" payment module
   *
   * @var string
   */
  var $code;
  /**
   * $title is the displayed name for this payment method
   *
   * @var string
   */
  var $title;
  /**
   * $description is a soft name for this payment method
   *
   * @var string
   */
  var $description;
  /**
   * module's icon
   *
   * @var string
   */
  var $icon;
  /**
   * $enabled determines whether this module shows or not... during checkout.
   *
   * @var boolean
   */
  var $enabled;
  /**
   * $webservice_adress webservice address
   *
   * @var string
   */
  var $webservice_adress;
  /**
   * constructor
   *
   * @return chronopostpickme
   */
  function chronopostpickme() {
    global $order, $db;

    $this->code = 'chronopostpickme';
    $this->title = MODULE_SHIPPING_CHRONOPOSTPICKME_TEXT_TITLE;
    $this->description = MODULE_SHIPPING_CHRONOPOSTPICKME_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_SHIPPING_CHRONOPOSTPICKME_SORT_ORDER;
    $this->icon = '';
    $this->tax_class = MODULE_SHIPPING_CHRONOPOSTPICKME_TAX_CLASS;
    $this->tax_basis = MODULE_SHIPPING_CHRONOPOSTPICKME_TAX_BASIS;
    $this->enabled = ((MODULE_SHIPPING_CHRONOPOSTPICKME_STATUS == 'True') ? true : false);
    $this->webservice_adress = MODULE_SHIPPING_CHRONOPOSTPICKME_WEBSERVICE;


    if (
      isset($_GET['set']) && $_GET['set']=='shipping' &&
      isset($_GET['module']) && $_GET['module']=='chronopostpickme' &&
      isset($_GET['action']) && $_GET['action']=='edit') {
       $this->updateDatabase();
    }

    if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_CHRONOPOSTPICKME_ZONE > 0) ) {
      $check_flag = false;
      $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . "
                             where geo_zone_id = '" . MODULE_SHIPPING_CHRONOPOSTPICKME_ZONE . "'
                             and zone_country_id = '" . $order->delivery['country']['id'] . "'
                             order by zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->delivery['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }
  }
  /**
   * Obtain quote from shipping system/calculations
   *
   * @param string $method
   * @return array
   */
  function quote($method = '') {
    global $order;

    $title = MODULE_SHIPPING_CHRONOPOSTPICKME_TEXT_WAY;
    if ($_POST['shipping'] == 'chronopostpickme_chronopostpickme') {
      $title = 'ChronoPost PickMe - '.$this->getStore($_POST['chronopostpickme_store']);
    }

    $this->quotes = array('id' => $this->code,
                          'module' => MODULE_SHIPPING_CHRONOPOSTPICKME_TEXT_TITLE,
                          'methods' => array(array('id' => $this->code,
                                                   'title' => $title,
                                                   'cost' => MODULE_SHIPPING_CHRONOPOSTPICKME_COST)));

    if ($this->tax_class > 0) {
      $this->quotes['tax'] = zen_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
    }

    if (zen_not_null($this->icon)) $this->quotes['icon'] = zen_image($this->icon, $this->title);

    return $this->quotes;
  }
  /**
   * Check to see whether module is installed
   *
   * @return boolean
   */
  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_CHRONOPOSTPICKME_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
  }
  /**
   * Install the shipping module and its configuration settings
   *
   */
  function install() {
    global $db;
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Store Pickup Shipping', 'MODULE_SHIPPING_CHRONOPOSTPICKME_STATUS', 'True', 'Do you want to offer In Store rate shipping?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shipping Cost', 'MODULE_SHIPPING_CHRONOPOSTPICKME_COST', '0.00', 'The shipping cost for all orders using this shipping method.', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_CHRONOPOSTPICKME_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '6', '0', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Tax Basis', 'MODULE_SHIPPING_CHRONOPOSTPICKME_TAX_BASIS', 'Shipping', 'On what basis is Shipping Tax calculated. Options are<br />Shipping - Based on customers Shipping Address<br />Billing Based on customers Billing address<br />Store - Based on Store address if Billing/Shipping Zone equals Store zone', '6', '0', 'zen_cfg_select_option(array(\'Shipping\', \'Billing\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Shipping Zone', 'MODULE_SHIPPING_CHRONOPOSTPICKME_ZONE', '0', 'If a zone is selected, only enable this shipping method for that zone.', '6', '0', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_CHRONOPOSTPICKME_SORT_ORDER', '0', 'Sort order of display.', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('WebService Address', 'MODULE_SHIPPING_CHRONOPOSTPICKME_WEBSERVICE', 'https://83.240.239.170:7554/ChronoWSB2CPointsv3/GetB2CPoints_v3Service?wsdl', 'WebService Address', '6', '0', now())");

    $db->Execute("CREATE TABLE IF NOT EXISTS `chronopost_pickme_shop_orders` (
      `id_order` int(10) unsigned NOT NULL,
      `id_pickme_shop` int(10) unsigned NOT NULL,
      PRIMARY KEY  (`id_order`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

    $db->Execute("CREATE TABLE IF NOT EXISTS `chronopost_pickme_shops` (
      `id_pickme_shop` int(10) unsigned NOT NULL auto_increment,
      `pickme_id` varchar(30) NULL,
      `name` varchar(255) NULL,
      `address` varchar(1000) NULL,
      `location` varchar(400) NULL,
      `postal_code` varchar(20) NULL,
      PRIMARY KEY  (`id_pickme_shop`),
      UNIQUE KEY `pickme_id` (`pickme_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

  }
  /**
   * Remove the module and all its settings
   *
   */
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key like 'MODULE\_SHIPPING\_CHRONOPOSTPICKME\_%'");
  }
  /**
   * Internal list of configuration keys used for configuration of the module
   *
   * @return array
   */
  function keys() {
    return array('MODULE_SHIPPING_CHRONOPOSTPICKME_STATUS', 'MODULE_SHIPPING_CHRONOPOSTPICKME_COST', 'MODULE_SHIPPING_CHRONOPOSTPICKME_TAX_CLASS', 'MODULE_SHIPPING_CHRONOPOSTPICKME_TAX_BASIS', 'MODULE_SHIPPING_CHRONOPOSTPICKME_ZONE', 'MODULE_SHIPPING_CHRONOPOSTPICKME_SORT_ORDER','MODULE_SHIPPING_CHRONOPOSTPICKME_WEBSERVICE');
  }

  function getStore($id) {
    global $db;
    $res = $db->Execute('select * from chronopost_pickme_shops where id_pickme_shop ='.$id);
    return $res->fields['pickme_id'] . ' - ' . $res->fields['name'].' '.$res->fields['address'].' '.$res->fields['postal_code'].' '.$res->fields['location'];
  }

  function getStores() {
    global $db;
    $res = $db->Execute('select * from chronopost_pickme_shops order by location');
    return $res;
  }

  function updateDatabase()
  {
    global $db;
    $string = $this->webservice_adress;

    if ($string == '') {
      $string = "https://83.240.239.170:7554/ChronoWSB2CPointsv3/GetB2CPoints_v3Service?wsdl";
    }
    try {
      $client = new SoapClient($string);

      $result = $client->getPointList_V3();
      foreach ($result->return->lB2CPointsArr as $message) {
          $query = '
            INSERT INTO `chronopost_pickme_shops`
                   (pickme_id, name, address, postal_code, location)
            VALUES ("'.$message->Number.'", "'.$message->Name.'", "'.$message->Address.'", "'.$message->PostalCode.'", "'.$message->PostalCodeLocation.'")
            ON DUPLICATE KEY UPDATE pickme_id=pickme_id
            ';
          $db->Execute($query);
      }
    } catch (Exception $e) {
      return true;
    }
    $res = $this->getStores();
    $storesJS = array();
    while (!$res->EOF) {
      $storesJs[] = '{id:"'.$res->fields['id_pickme_shop'].'",name:"'.$res->fields['name'].'",title:"'.$res->fields['name'].' '.$res->fields['address'].' '.$res->fields['postal_code'].' '.$res->fields['location'].'",address:"'.$res->fields['address'].'",location:"'.$res->fields['location'].'"}';
      $res->MoveNext();
    }

    $data = str_replace("{STORES}", implode(",\n",$storesJs), $this->JS);
    $ok = file_put_contents($_SERVER['DOCUMENT_ROOT'].'/chronopostpickme.js',$data);
  }

  var $JS = <<<EOF
function capitaliseFirstLetter(string)
{
    string = string.toLowerCase();
    return string.charAt(0).toUpperCase() + string.slice(1);
}
chronopostpickme = {
  holder : null,

  stores : [
    {STORES}
  ],

  init : function(){
    chronopostpickme.holder = document.getElementById('chronopostpickmeHolder');
    chronopostpickme.holder.innerHTML = chronopostpickme.buildSelect();
  },

  buildSelect : function() {
    var select = '';
    var curLocation = null;

    select += '<select style="width:300px" onchange="chronopostpickme.saveStore();" id="chronopostpickme_store" name="chronopostpickme_store"><optgroup';
    for (var i=0;i<chronopostpickme.stores.length;i++) {
      var address = chronopostpickme.stores[i].address.split(' ');
      for (j=0;j<address.length;j++) address[j] = capitaliseFirstLetter(address[j]);
      var address1 = address.join(' ');

      if (curLocation==null) {
          select += ' label="'+chronopostpickme.stores[i].location+'">';
          curLocation = chronopostpickme.stores[i].location;
      }
      if (curLocation!=null && chronopostpickme.stores[i].location!=curLocation) {
          curLocation = chronopostpickme.stores[i].location;
          select += '</optgroup> <optgroup label="'+curLocation+'">';
      }

      select += '<option value="'+chronopostpickme.stores[i].id+'">'+chronopostpickme.stores[i].name + ' - ' + address1+'</option>';
    }
    select += '</optgroup></select>';

    return select;
  },

  saveStore : function() {
    var obj = document.getElementById('chronopostpickme_store');
    var val = obj.options[obj.selectedIndex].value;

    chronopostpickme.createCookie('chronopostpickme_store',val);

    console.log(chronopostpickme.readCookie('chronopostpickme_store'));
  },

  createCookie : function(name,value,days) {
    if (days) {
      var date = new Date();
      date.setTime(date.getTime()+(days*24*60*60*1000));
      var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
  },

  readCookie : function(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
      var c = ca[i];
      while (c.charAt(0)==' ') c = c.substring(1,c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
  },

  eraseCookie : function(name) {
    createCookie(name,"",-1);
  }
};

window.onload = function(){
  if (location.search.indexOf('checkout_shipping')!=-1) {
    chronopostpickme.init();
  }
};

EOF;

}
?>