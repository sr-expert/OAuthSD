<?php
/**
 * Utilisation de l'action supprimer pour l'objet client_id
 *
 * @plugin     OAuth 2.0
 * @copyright  2016
 * @author     DnC
 * @licence    GNU/GPL
 * @package    SPIP\Oauth\Action
 */

if (!defined('_ECRIRE_INC_VERSION')) return;



/**
 * Action pour supprimer un·e client_id
 *
 * Vérifier l'autorisation avant d'appeler l'action.
 *
 * @param null|int $arg
 *     Identifiant à supprimer.
 *     En absence de id utilise l'argument de l'action sécurisée.
**/
function action_supprimer_client_id_dist($arg=null) {
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	$arg = intval($arg);

	// cas suppression
	if ($arg) {
		sql_delete('spip_oa_clients',  'client_id=' . sql_quote($arg));
	}
	else {
		spip_log("action_supprimer_client_id_dist $arg pas compris");
	}
}