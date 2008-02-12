<?
//
/*************************************************************************
* 
*	Lansuite - Webbased LAN-Party Management System
*	-----------------------------------------------
*
*	(c) 2001-2003 by One-Network.Org
*
*	Lansuite Version:	2.0
*	File Version:		2.0
*	Filename: 			minesweeper
*	Module: 			Minesweeper
*	Main editor: 		jochen@one-network.org
*	Last change: 		24.05.2004 13:35
*	Description: 		The Classic Minesweeper Game, you all know
*	Remarks: 		
*
**************************************************************************/

$headermenuitem	= $vars["headermenuitem"];
$action = $_GET["action"];

$dsp->NewContent($lang["games"]["ms_caption"], $lang["games"]["ms_subcaption"]);

$menunames[] = $lang["games"]["ms_navi_start"];
$menunames[] = $lang["games"]["ms_navi_highscore"];
$dsp->AddHeaderMenu($menunames, "?mod=games&action=minesweeper", $headermenuitem);

if ($headermenuitem == 1) $_GET["step"] = 1;
if ($headermenuitem == 2) $_GET["step"] = 5;


switch ($_GET["step"]) {
	case 2:
		if ($_POST["rows"] > 20) {
			$func->information($lang["games"]["ms_err_maxrow"], "?mod=games&action=minesweeper");

		} elseif ($_POST["columns"] > 40) {
			$func->information($lang["games"]["ms_err_maxcol"], "?mod=games&action=minesweeper");

		} elseif ($_POST["mines"] > $_POST["rows"] * $_POST["columns"]) {
			$func->information($lang["games"]["ms_err_maxmine"], "?mod=games&action=minesweeper");

		} elseif ($_POST["mines"] < 5) {
			$func->information($lang["games"]["ms_err_minmine"], "?mod=games&action=minesweeper");

		} else {
			$tmp_nick = rand(0, 100000);

			$db->query("REPLACE INTO {$config["tables"]["game_hs"]}
				SET game = 'mw_tmp', nick = '$tmp_nick', score = ". time() ."
				");

			$templ['games']['minesweeper']['rows'] = $_POST["rows"];
			$templ['games']['minesweeper']['columns'] = $_POST["columns"];
			$templ['games']['minesweeper']['mines'] = $_POST["mines"];

			$templ['games']['minesweeper']['link_won'] = "?mod=games&action=minesweeper&step=3&tmp_nick=$tmp_nick";

			$templ['games']['minesweeper']['generate_field'] = "";
			for ($i=0; $i< $_POST["rows"]; $i++) {
				$templ['games']['minesweeper']['generate_field'] .= "<tr>";
				for ($j=0; $j< $_POST["columns"]; $j++) {
					$templ['games']['minesweeper']['generate_field'] .= "<td><input type=\"button\" value=\" \" name=\"";
					if ($i<10) $templ['games']['minesweeper']['generate_field'] .= "0";
					$templ['games']['minesweeper']['generate_field'] .= $i;
					if ($j<10) $templ['games']['minesweeper']['generate_field'] .= "0";
					$templ['games']['minesweeper']['generate_field'] .= "$j\" style=\"width:19;height:19;border:solid 1px 000000\" onClick=\"Check(this)\"></td>";
				}
				$templ['games']['minesweeper']['generate_field'] .= "</tr>";
			}

			$dsp->AddSingleRow($dsp->FetchModTpl("games", "minesweeper"));
		}
	break;

	case 3:
		$dsp->AddSingleRow("<b>". $lang["games"]["ms_congrats"] ."</b>");
		$dsp->AddHRuleRow();

		$db->query("UPDATE {$config["tables"]["game_hs"]}
			SET score = ". time() ." - score
			WHERE (nick = '{$_GET["tmp_nick"]}' AND game = 'mw_tmp')
			");

		$dsp->SetForm("?mod=games&action=minesweeper&step=4&tmp_nick={$_GET["tmp_nick"]}");
		$dsp->AddSingleRow($lang["games"]["ms_go_highscore"]);
		$dsp->AddDoubleRow($lang["games"]["ms_time"], $score);
		$dsp->AddTextFieldRow("nick", $lang["games"]["ms_nick"], $auth["username"], "");
		$dsp->AddFormSubmitRow("next");

		$dsp->AddBackButton("?mod=games", "games/minesweeper");
	break;

	case 4:
		$db->query("UPDATE {$config["tables"]["game_hs"]}
			SET game = 'mw', nick = '". $func->text2db($_POST["nick"]) ."'
			WHERE (nick = '{$_GET["tmp_nick"]}' AND game = 'mw_tmp')
			");

		if ($db->get_affected_rows() > 0) $func->confirmation($lang["games"]["ms_hs_success"], "?mod=games&action=minesweeper&headermenuitem=2");
		else $func->information("Der angegebene tempor�re Nick wurde nicht gefunden. Das Ergebnis konnte daher leider nicht eingetragen werden.", "?mod=games&action=minesweeper&headermenuitem=2");
	break;

	case 5:
		$dsp->AddSingleRow($lang["games"]["ms_hs_list"]);

		$hs_liste = $db->query("SELECT nick, score from {$config["tables"]["game_hs"]} WHERE game='mw' ORDER BY score;");
		while($entry = $db->fetch_array($hs_liste)){
			$dsp->AddDoubleRow($entry['nick'], $entry['score'] ." Sek.");
		}
		$db->free_result($hs_liste);

		$dsp->AddBackButton("?mod=games", "games/minesweeper");
	break;

	default:
		$dsp->SetForm("?mod=games&action=minesweeper&step=2");
		$dsp->AddTextFieldRow("rows", $lang["games"]["ms_rows"], "12", "");
		$dsp->AddTextFieldRow("columns", $lang["games"]["ms_cols"], "20", "");
		$dsp->AddTextFieldRow("mines", $lang["games"]["ms_mines"], "25", "");
		$dsp->AddFormSubmitRow("next");

		$dsp->AddBackButton("?mod=games", "games/minesweeper");
	break;
}

$dsp->AddContent();
?>
