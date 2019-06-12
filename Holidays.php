<?php
class HolidaysPlugin extends MantisPlugin {
 
	function register() {
		$this->name        = 'Holidays';
		$this->description = lang_get( 'holidays_description' );
		$this->version     = '0.90';
		$this->requires    = array('MantisCore'       => '1.2.0',);
		$this->author      = 'Cas Nuy';
		$this->contact     = 'Cas-at-nuy.info';
		$this->url         = 'http://www.nuy.info';
	}

	function init() { 
		// Allow defining holiday
		event_declare('EVENT_DEFINE_USER');
		// Delete holiday settings when user is deleted
		event_declare('EVENT_DELETE_USER');

		plugin_event_hook('EVENT_DEFINE_USER', 'DefHoliday');
		plugin_event_hook('EVENT_DELETE_USER', 'DelHoliday');
		plugin_event_hook('EVENT_LAYOUT_BODY_BEGIN', 'WarnHoliday');
		plugin_event_hook('EVENT_NOTIFY_USER_INCLUDE', 'MailHoliday');
		
	}

	function DefHoliday(){
		include 'plugins/Holidays/pages/holiday_form.php';
	}
	
	function WarnHoliday(){
		include 'plugins/Holidays/pages/holiday_warning.php';
	}
	
	function MailHoliday($p_event, $p_bug_id){
		$bug_info = bug_get( $p_bug_id, true ); 
		$handler = $bug_info->handler_id;
		// get the handler of the issue
		$hol_table	= plugin_table('period');
		$handler	= $bug_info->handler_id ;
		$sql 		=  "select * from $hol_table where user_id=$handler";
		$result	= db_query_bound($sql);
		// check if this person is on holiday		
		if (db_num_rows($result) > 0) {
			$row = db_fetch_array($result);
			// first check absent indicator
			if ($row['absent']== 1){
				// now check if today is within period defined
				$today  = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
				if (($today>= $row['periodfrom']) and ($today <= $row['periodto'])){
					// add the backup to the recipients
					// has to be an array
					$mail = array();
					$mail[1] = $row['user_backup'];
					return $mail ;
 				}
			}
		}
		return;
	}
		
	function DelHoliday($p_event,$f_user_id){
 		$hol_table	= plugin_table('period');
		$sql = "delete from $hol_table where user_id=$f_user_id";
		$result		= db_query_bound($sql);
	}

	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'period' ), "
						user_id 			I       NOTNULL AUTOINCREMENT UNSIGNED PRIMARY,
						user_backup			I		NOTNULL UNSIGNED ,
						periodfrom			I		,
						periodto			I		,
						absent				I		
						" ) ),
		);
	} 
}