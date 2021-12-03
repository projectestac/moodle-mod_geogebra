<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Catalan strings for geogebra
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'GeoGebra';
$string['modulenameplural'] = 'GeoGebra';

$string['noattempts'] = '-';
$string['name'] = 'Nom';
$string['choosescripttype'] = 'Escull el tipus d\'activitat';
$string['manualgrade'] = 'Qualificació manual?'; //Unused
$string['contentheader'] = 'Contingut';
$string['width'] = 'Amplada';
$string['height'] = 'Alçada';
$string['seed'] = 'Llavor';
$string['urlggb'] = 'URL GGB personalitzat';
$string['urlggb_help'] = 'URL alternatiu del fitxer deployggb.js on es troba la distribució del GeoGebra. Si està definit, s\'utilizarà aquest URL en lloc del definit per defecte a la configuració del mòdul. En general, aquest camp es pot deixar en blanc.';
$string['showsubmit'] = 'Mostra el botó d\'entrega';
$string['settings'] = 'Paràmetres';
$string['maxattempts'] = 'Número màxim d\'intents';
$string['grademethod'] = 'Mètode de qualificació';
$string['nograding'] = 'Sense qualificar';
$string['average'] = 'Mitjana';
$string['highestattempt'] = 'Millor intent';
$string['lowestattempt'] = 'Pitjor intent';
$string['firstattempt'] = 'Primer intent';
$string['lastattempt'] = 'Darrer intent';
$string['viewattempts'] = 'Visualitza intents';
$string['comment'] = 'Comentari';

$string['unlimitedattempts'] = 'Aquesta activitat no té límit d\'intents';
$string['lastattemptremaining'] = 'Aquest és el teu darrer intent en aquesta activitat';
$string['nomoreattempts'] = 'Ja has realitzat tots els intents possibles per a aquesta activitat';
$string['attemptsremaining'] = 'Intents disponibles per a aquesta activitat: ';

$string['activitynotopened'] = 'Aquesta activitat encara no està disponible';
$string['activityclosed'] = 'Aquesta activitat ja no està disponible';

$string['review'] = 'Revisió de';
$string['report'] = 'Informe de';
$string['for'] = 'per';
$string['description'] = 'Descripció';
$string['weight'] = 'Amplada';
$string['grade'] = 'Qualificació';
$string['total'] = 'Total';
$string['attempts'] = 'Intents';
$string['attempt'] = 'Intent';
$string['duration'] = 'Temps';

$string['errorattempt'] = 'S\'ha produït un error. No s\'ha pogut desar l\'intent.';

$string['viewtab'] = 'Mostra';
$string['results'] = 'Resultats';
$string['reviewtab'] = 'Revisió';

$string['availabledate'] = 'Disponible des de';
$string['duedate'] = 'Fins a';

$string['filename'] = 'Nom del fitxer';
$string['enableRightClick'] = 'Habilita el botó dret';
$string['enableLabelDrags'] = 'Permet arrossegar les etiquetes';
$string['showResetIcon'] = 'Mostra la icona de reiniciar';
$string['showMenuBar'] = 'Mostra la barra de menú';
$string['showToolBar'] = 'Mostra la barra d\'eines';
$string['showToolBarHelp'] = 'Mostra l\'ajuda de la barra d\'eines';
$string['showAlgebraInput'] = 'Mostra la barra d\'inserció'; //Unused
$string['useBrowserForJS'] = 'Utilitza el JavaScript des de:';
$string['useBrowserForJS_html'] = 'HTML (cert)';
$string['useBrowserForJS_geogebra'] = 'Fitxer GeoGebra (fals)';
$string['functionalityoptionsgrp'] = 'Funcionalitats';
$string['interfaceoptionsgrp'] = 'Interfície';
$string['filenotfound'] = 'El fitxer indicat no existeix';
$string['httpnotallowed'] = 'No és possible utilitzar fitxers remots';

$string['submitandfinish'] = 'Entrega i acaba';
$string['savewithoutsubmitting'] = 'Desa sense entregar';
$string['redirecttocourse'] = 'L\'activitat s\'ha desat correctament. S\'està tornant a la pàgina d\'inici';
$string['unfinished'] = 'No finalitzat';
$string['language']='Idioma';
$string['resumeattempt'] = 'Continuació d\'un intent anterior';
$string['coursewithoutstudents'] = 'No hi ha estudiants inscrits en el curs actual';
$string['deleteallattempts'] = 'Suprimeix tots els intents';
$string['view'] = 'Visualitza';
$string['gradeit'] = 'Qualificació';
$string['timing'] = 'Temporització';
$string['ungraded'] = 'Sense qualificar';
//$string['save'] = 'Desa';
$string['autograde'] = 'Activitat autopuntuable';


$string['savechanges'] = 'Desa els canvis';
$string['discardchanges'] = 'Torna sense desar';

$string['privacy'] = 'Privacitat dels resultats';
$string['privacy:metadata:geogebra_attempts'] = 'Informació sobre els intent/s realitzats per a cada activitat de geogebra';
$string['privacy:metadata:geogebra_attempts:vars'] = 'Dades relacionades amb l\'intent del usuari';
$string['privacy:metadata:geogebra_attempts:gradecomment'] = 'El comentari de la nota del intent de l\'acrivitat  geogebra.';
$string['privacy:metadata:geogebra_attempts:userid'] = 'L\'ID del usuari que ha realitzat l\'intentot.';
$string['privacy:metadata:geogebra_attempts:finished'] = 'El timestamp que indica la finalització del intent del usuari.';
$string['privacy:metadata:geogebra_attempts:geogebra'] = 'L\'ID de l\'activitat geogebra';
$string['privacy:metadata:geogebra_attempts:dateteacher'] = 'El timestamp que indica la finalització per part del professor';
$string['privacy:metadata:geogebra_attempts:datestudent'] = 'El timestamp que indica la finalització per part del estudiant';

/* Revision Moodle 2 */
$string['modulename_help'] = '<p><a href="https://www.geogebra.org" target="_blank">GeoGebra</a> és una aplicació de matemàtica dinàmica, gratuïta, lliure i multiplataforma, enfocada a tots els nivells educatius, que aglutina la geometria, l\'àlgebra, el full de càlcul, l\'estadística i l\'anàlisi, en un únic paquet integrat, molt fàcil d\'utilitzar.</p>
<p>Per aquest motiu, el <a href="http://www.gencat.cat/ensenyament/" target="_blank">Departament d\'Ensenyament de Catalunya</a>, en col·laboració amb l\'<a href="http://acgeogebra.cat/" target="_blank">Associació Catalana de GeoGebra</a> (ACG) i l\'equip de desenvolupament de GeoGebra han implementat aquest mòdul que permet la incorporació d\'aquest tipus d\'activitats a Moodle. Les seves característiques principals són:
<ul>
    <li>Permet incrustar activitats GeoGebra a qualsevol curs de forma molt senzilla.</li>
    <li>Facilita el seguiment ja que guarda la puntuació, data, durada i construccions de cadascun dels intents que realitza l\'alumnat.</li>
    <li>L\'alumnat pot desar l\'estat de les activitats realitzades per continuar-les en un altre moment.</li>
</ul></p>';
$string['pluginname'] = 'GeoGebra';
$string['pluginadministration'] = 'Administració de GeoGebra';
$string['geogebra:view'] = 'Visualitza GeoGebra';
$string['geogebra:submit'] = 'Envia GeoGebra';
$string['geogebra:grade'] = 'Avalua GeoGebra';

$string['geogebra:addinstance'] = 'Afegeix una activitat GeoGebra';
$string['filetype'] = 'Tipus';
$string['filetype_help'] = 'Aquest paràmetre determina com s\'incorporarà l\'activitat GeoGebra al curs. Hi ha dues opcions:

* Fitxer pujat - Permet escollir un fitxer ".ggb" vàlid mitjançant el selector d\'arxius.
* URL extern - Permet especificar el URL d\'una activitat GeoGebra. Nota: El URL ha de començar amb http(s) o www i contenir un fitxer ".ggb" vàlid.';
$string['filetypeexternal'] = 'URL extern';
$string['filetypelocal'] = 'Fitxer pujat';
$string['invalidgeogebrafile'] = 'S\'ha especificat un fitxer GeoGebra no vàlid. El fitxer ha de tenir l\'extensió ".ggb".';
$string['invalidurl'] = 'S\'ha especificat un URL no vàlid. El URL ha de començar amb http(s) i ha d\'enllaçar a un fitxer ".ggb" vàlid.';
$string['geogebraurl'] = 'URL';
$string['geogebraurl_help'] = 'Localitzeu la construcció a <a href="https://www.geogebra.org/" target="_blank">geogebra.org</a>, aneu a <strong>Detalls</strong>, feu clic amb el botó dret del ratolí al botó <strong>Descarrega</strong> i seleccioneu <strong>Copia l\'adreça de l\'enllaç</strong>. Finalment, situeu-vos al camp URL i enganxeu l\'enllaç amb <strong>Ctrl+V</strong>.<br/>Per a un exemple pas a pas d\'aquesta operació vegeu: <a href="https://youtu.be/qbp-RuM4NpU" target="_blank">https://youtu.be/qbp-RuM4NpU</a>';
$string['seed_help'] = 'La llavor ha de ser un número enter positiu entre 0 i 99. Si és zero, les activitats Geogebra generades utilitzant aleatorietat seran diferents cada vegada que es carregui la pàgina. Si no és zero, en tornar-la a carregar es mostrarà la mateixa versió. Utilitzar dues llavors diferents generarà dues versions diferents. Introduïu un zero per fer que cada estudiant tingui un exercici diferent. Introduïu un valor diferent de zero per fer que tots els estudiants rebin el mateix exercici.';
$string['geogebrafile'] = 'Fitxer GeoGebra';
$string['geogebrafile_help'] = 'El fitxer ".ggb" que conté l\'activitat GeoGebra.';
$string['urledit'] = 'Fitxer GeoGebra';
$string['urledit_help'] = 'El fitxer ".ggb" que conté l\'activitat GeoGebra.';

$string['datestudent'] = 'Darrera modificació (tramesa)';
$string['dateteacher']= 'Darrera modificació (qualificació)';
$string['status'] = 'Estat';
$string['viewattempt'] = 'Visualitza';
$string['previewtab'] = 'Previsualitza';
$string['preview_geogebra'] = 'Previsualitza l\'activitat Geogebra';

$string['notopenyet'] = 'Ho sentim, aquesta activitat no estarà disponible fins {$a}';
$string['expired'] = 'Ho sentim, aquesta activitat es va tancar el {$a} i, per tant, ja no està disponible';
$string['msg_noattempts']= 'Ja has fet aquesta activitat el nombre m&agrave;xim de vegades perm&egrave;s.';
$string['lastmodifiedsubmission'] = $string['datestudent'];
$string['lastmodifiedgrade'] = $string['dateteacher'];
$string['viewattempttab'] = 'Intent';
$string['extractedfromggb'] = 'arxius extrets des del ggb';
$string['msg_nosessions'] = 'Aquesta activitat Geogebra encara no té cap sessió';

// Settings.
$string['configintro'] = 'Els valors que es configuren aquí defineixen els URL per defecte des d\'on es carregarà el GeoGebra.';
$string['deployggb'] = 'URL de distribució del GeoGebra';
$string['deployggb_desc'] = 'URL del fitxer deployggb.js de distribució del GeoGebra. Habitualment es troba a geogebra.org. Pot ser un fitxer local.';
$string['fflate'] = 'URL de distribució del fflate';
$string['fflate_desc'] = 'URL de l\'script fast flate, utilitzat per descomprimir els fitxers javascript del GeoGebra.';
