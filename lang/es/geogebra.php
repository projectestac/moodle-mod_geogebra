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
 * Spanish strings for geogebra
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
$string['name'] = 'Nombre';
$string['choosescripttype'] = 'Escoge el tipo de actividad';
$string['manualgrade'] = 'Calificación manual?'; //Unused
$string['contentheader'] = 'Contenido';
$string['width'] = 'Ancho';
$string['height'] = 'Alto';
$string['seed'] = 'Semilla';
$string['urlggb'] = 'URL GGB personalizado';
$string['urlggb_help'] = 'URL alternativo del fichero deployggb.js donde se encuentra la distribución del GeoGebra. Si está definido, se utilizará este URL en lugar del definido por defecto en la configuración del módulo. En general, este campo se puede dejar en blanco.';
$string['showsubmit'] = 'Muestra el botón de entrega';
$string['settings'] = 'Parámetros';
$string['maxattempts'] = 'Número máximo de intentos';
$string['grademethod'] = 'Método de calificación';
$string['nograding'] = 'Sin calificar';
$string['average'] = 'Media';
$string['highestattempt'] = 'Mejor intento';
$string['lowestattempt'] = 'Peor intento';
$string['firstattempt'] = 'Primer intento';
$string['lastattempt'] = 'Último intento';
$string['viewattempts'] = 'Ver intentos';
$string['comment'] = 'Comentario';

$string['unlimitedattempts'] = 'Esta actividad no tiene límite de intentos';
$string['lastattemptremaining'] = 'Este es tu último intento en esta actividad';
$string['nomoreattempts'] = 'Ya has realizado todos los intentos posibles para esta actividad';
$string['attemptsremaining'] = 'Intentos disponibles para esta actividad: ';

$string['activitynotopened'] = 'Esta actividad aún no está disponible';
$string['activityclosed'] = 'Esta actividad ya no está disponible';

$string['review'] = 'Revisión de';
$string['report'] = 'Informe de';
$string['for'] = 'para';
$string['description'] = 'Descripción';
$string['weight'] = 'Ancho';
$string['grade'] = 'Calificación';
$string['total'] = 'Total';
$string['attempts'] = 'Intentos';
$string['attempt'] = 'Intento';
$string['duration'] = 'Tiempo';

$string['errorattempt'] = 'Se ha producido un error. No se ha podido guardar el intento.';

$string['viewtab'] = 'Muestra';
$string['results'] = 'Resultados';
$string['reviewtab'] = 'Revisión';

$string['availabledate'] = 'Disponible desde';
$string['duedate'] = 'Hasta';

$string['filename'] = 'Nombre del archivo';
$string['enableRightClick'] = 'Habilita el botón derecho';
$string['enableLabelDrags'] = 'Permite arrastrar las etiquetas';
$string['showResetIcon'] = 'Muestra el icono de reiniciar';
$string['showMenuBar'] = 'Muestra la barra de menú';
$string['showToolBar'] = 'Muestra la barra de herramientas';
$string['showToolBarHelp'] = 'Muestra la ayuda de la barra de herramientas';
$string['showAlgebraInput'] = 'Muestra la barra de inserción'; //Unused
$string['useBrowserForJS'] = 'Utilizar el JavaScript desde:';
$string['useBrowserForJS_html'] = 'HTML (cierto)';
$string['useBrowserForJS_geogebra'] = 'Archivo GeoGebra (falso)';
$string['functionalityoptionsgrp'] = 'Funcionalidades';
$string['interfaceoptionsgrp'] = 'Interfaz';
$string['filenotfound'] = 'El archivo indicado';
$string['httpnotallowed'] = 'No es posible utilizar archivos remotos';

$string['submitandfinish'] = 'Entrega y termina';
$string['savewithoutsubmitting'] = 'Guarda sin entregar';
$string['redirecttocourse'] = 'La actividad se ha guardado correctamente. Se está volviendo a la pantalla de inicio.';
$string['unfinished'] = 'No finalizado';
$string['language'] = 'Idioma';
$string['resumeattempt'] = 'Continuación de un intento anterior';
$string['coursewithoutstudents'] = 'No hay estudiantes inscritos en este curso';
$string['deleteallattempts'] = 'Eliminar todos los intentos';
$string['view'] = 'Ver';
$string['gradeit'] = 'Calificación';
$string['timing'] = 'Temporización';
$string['ungraded'] = 'Sin calificar';
$string['save'] = 'Guardar';
$string['autograde'] = 'Actividad autopuntuable';


$string['savechanges'] = 'Guardar los cambios';
$string['discardchanges'] = 'Volver sin guardar';

$string['privacy'] = 'Privacidad de los resultados';
$string['privacy:metadata:geogebra_attempts'] = 'Informació sobre los intento/s realitzados para cada actividad geogebra';
$string['privacy:metadata:geogebra_attempts:vars'] = 'Datos relacionados con el intento del usuario';
$string['privacy:metadata:geogebra_attempts:gradecomment'] = 'El comentario de la nota del intento de la acrividad geogebra.';
$string['privacy:metadata:geogebra_attempts:userid'] = 'El ID del usuario que ha realitzado el intento.';
$string['privacy:metadata:geogebra_attempts:finished'] = 'El timestamp que indica la finalitzación del intento del usuario.';
$string['privacy:metadata:geogebra_attempts:geogebra'] = 'El ID de la activividad geogebra';
$string['privacy:metadata:geogebra_attempts:dateteacher'] = 'El timestamp que indica la finalitzación por parte del profesor';
$string['privacy:metadata:geogebra_attempts:datestudent'] = 'El timestamp que indica la finalitzación por parte del estudiante';

/* Revision Moodle 2 */
$string['modulename_help'] = '<p><a href="http://www.geogebra.org" target="_blank">GeoGebra</a> es una aplicación de matemática dinamica, gratuita, libre y multiplataforma, enfocada a todos los niveles educativos, que engloba la geometría, el álgebra, la hoja de cálculo, la estadística, la probabilidad y el análisis, en un único paquete integrado, muy fácil de utilizar.</p>
<p>
Por ello, el <a href="http://www.gencat.cat/ensenyament/" target="_blank">Departament d\'Ensenyament de Cataluña</a> en colaboración con la <a href="http://acgeogebra.cat/" target="_blank">Asociación Catalana de GeoGebra</a> (ACG) y el equipo de desarrollo de GeoGeobra han implementado este módulo que permite la incorporación de este tipo de actividades en Moodle. Sus principales características son:
<ul>
    <li>Permite incrustar actividades GeoGebra en cualquier curso Moodle de forma muy sencilla.</li>
    <li>Facilita el seguimiento ya que guarda la puntuación, fecha, duración y construcción de cada uno de los intentos que realiza el alumnado.</li>
    <li>El alumnado puede guardar el estado de las actividades realizadas para continuarlas en otro momento.</li>
</ul>
</p>';
$string['pluginname'] = 'GeoGebra';
$string['pluginadministration'] = 'Administración de GeoGebra';
$string['geogebra:view'] = 'Visualizar GeoGebra';
$string['geogebra:submit'] = 'Enviar GeoGebra';
$string['geogebra:grade'] = 'Evaluar GeoGebra';

$string['geogebra:addinstance'] = 'Añadir un GeoGebra';
$string['header_geogebra']='Parámetros de GeoGebra';
$string['header_score']='Parámetros de evaluación de GeoGebra';
$string['filetype'] = 'Tipo';
$string['filetype_help'] = 'Este parámetro determina cómo se incluye la actividad GeoGebra en el curso. Hay 2 opciones:

* Fichero subido - Posibilita escoger un fichero ".ggb" válido mediante el selector de archivos.
* URL externo - Posibilita especificar el URL de una actividad GeoGebra. NOTA: El URL debe empezar con https(s) o www y contener un fichero ".ggb" válido';
$string['filetypeexternal'] = 'URL externo';
$string['filetypelocal'] = 'Fichero subido';
$string['invalidgeogebrafile'] = 'Se ha especificado un fichero GeoGebra no válido. El fichero debe tener la extensión ".ggb".';
$string['invalidurl'] = 'Se ha especificado un URL no válido. El URL debe empezar con http(s) y enlazar a un fichero ".ggb" válido.';
$string['geogebraurl'] = 'URL';
$string['geogebraurl_help'] = 'Localiza la construcción en <a href="https://www.geogebra.org/" target="_blank">geogebra.org</a>, ve a <strong>Detalles</strong>, haz clic con el botón derecho del ratón en <strong>Descargar</strong> y selecciona <strong>Copiar la dirección del enlace</strong>. Finalmente, sitúate en el campo URL y pega el enlace con <strong>Ctrl+V</strong>.<br/>Para un ejemplo paso a paso de esta operación ver: <a href="https://youtu.be/qbp-RuM4NpU" target="_blank">https://youtu.be/qbp-RuM4NpU</a>.';
$string['seed_help'] = 'La semilla debe ser un número entero positivo entre 0 y 99. Si es cero, las actividades Geogebra generadas utilizando aleatoriedad serán diferentes cada vez que se cargue la página. Si no es cero, al cargarla de nuevo se mostrará la misma versión. Utilizar dos semillas diferentes generará dos versiones diferentes. Introduce un cero para que cada estudiante tenga un ejercicio diferente. Introduce un valor diferente de cero para que todos los estudiantes reciban el mismo ejercicio.';
$string['geogebrafile'] = 'Fichero GeoGebra';
$string['geogebrafile_help'] = 'El fichero ".ggb" que contiene la actividad GeoGebra.';
$string['urledit'] = 'Fichero GeoGebra';
$string['urledit_help'] = 'El fichero ".ggb" que contiene la actividad GeoGebra.';

$string['datestudent'] = 'Última modificación (entrega)';
$string['dateteacher']= 'Última modificación (calificación)';
$string['status'] = 'Estado';
$string['viewattempt'] = 'Visualizar';
$string['previewtab'] = 'Previsualizar';
$string['preview_geogebra'] = 'Previsualizar la actividad Geogebra';

$string['notopenyet'] = 'Esta actividad no estará disponible hasta {$a}';
$string['expired'] = 'Esta actividad se cerró el {$a} y, por lo tanto, ya no está disponible.';
$string['msg_noattempts']= 'Ya has realizado esta actividad el n&uacute;mero de veces m&agrave;ximo';
$string['lastmodifiedsubmission'] = $string['datestudent'];
$string['lastmodifiedgrade'] = $string['dateteacher'];
$string['viewattempttab'] = 'Intento';
$string['extractedfromggb'] = 'archivos extraidos desde el ggb';
$string['msg_nosessions'] = 'Esta actividad Geogebra todavía no tiene ninguna sessión';

// Settings.
$string['configintro'] = 'Los valores que se configuran aquí definen los URL por defecto des de donde se cargará el GeoGebra.';
$string['deployggb'] = 'URL de distribución de GeoGebra';
$string['deployggb_desc'] = 'URL del fichero deployggb.js de distribución de GeoGebra. Habitualmente se encuentra en geogebra.org. Puede ser un fichero local.';
$string['fflate'] = 'URL de distribución de fflate';
$string['fflate_desc'] = 'URL del script fast flate, utilizado para descomprimir los ficheros javascript de GeoGebra.';
