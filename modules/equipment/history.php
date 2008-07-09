<?php
ob_start();

if (!$cfg["equipment_shopid"]) $func->error(t('Es wurde noch keine Orgapage.Net-ShopID angegeben. Diese kann auf der Admin-Seite in den Moduleinstellungen unter \'Equipmentshop\' eingestellt werden'), "");
else {
    include "http://www.orgapage.net/pages/equip/shops/termine.php?action=history&id={$cfg["equipment_shopid"]}";

    if (!strpos(ob_get_contents(), "<table ")) $func->error(t('Es konnten keine Daten abgerufen werden. Evtl. ist der Orgapage.Net-Server momentan nicht erreichbar'), "");
    else {
        $dsp->NewContent(t('Termin-History'), t('Dies ist eine Liste aller vergangen Termine, bei denen wir unser Equipment bereits vermietet hatten'));
        $dsp->AddModTpl("equipment", "style");
        $dsp->AddSingleRow(ob_get_contents());
        $dsp->AddContent();
    }
}
ob_end_clean();
?>