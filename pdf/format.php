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
 * PDF question exporter.
 *
 * @package    qformat_pdf
 * @copyright  2005 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/vendor/autoload.php';

if (!function_exists('debug_to_console')) {
    function debug_to_console($data) {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);
        #echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
    }
}



function b_wrap($content) {
    $content = preg_replace('/<p[^<]*>/', '', $content);
    $content = preg_replace('/<\/p>/', '', $content);
    /*$content = preg_replace('/<br\/?>/', '', $content);*/
    return "<b>" . $content . "</b>";
}

function br_wrap($content) {
    $content = preg_replace('/<p[^<]*>/', '', $content);
    $content = preg_replace('/<\/p>/', '', $content);
    #$content = preg_replace('<br>', '', $content);
    $content = preg_replace("@\n@","",$content);
    return $content;
}

function break_wrap($content) {
    $content = preg_replace('<br />', '', $content);
    $content = preg_replace('</br />', '', $content);
return $content;
}

function pmatch($content) {
    $content = preg_replace('/<p[^<]*>/', '', $content);
    $content = preg_replace('/<\/p>/', '', $content);
    return $content;
}
/**
 * PDF question exporter.
 *
 * Exports questions as static HTML.
 *
 * @copyright  2005 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class qformat_pdf extends qformat_default {

    public function provide_export() {
        return true;
    }

    protected function repchar($text) {
        return $text;
    }

    protected function writequestion($question) {
        global $OUTPUT;
        global $DB;
        static $qcount = 0;

        // Turns question into string.
        // Question reflects database fields for general question and specific to type.

        // If a category switch, just ignore.
        if ($question->qtype=='category') {
            return '';
        }

        $oldanswers = $DB->get_records('question_answers', array('question' => $question->id), 'id ASC');
        // Initial string.
        $expout = '';
        $qcount++;
        $course = $this->course;
        $cname = $course->fullname;
        $quizid = required_param('id', PARAM_INT); // Course Module ID, or ...
        #$expout .= $quizid;
        $quizes = $DB->get_records_sql('SELECT * from {quiz} where id = ?',array($quizid));

        foreach($quizes as $i){
            $course = $i -> course;
            $qname = $i -> name;
            $qgrade = $i -> grade;
            $qintro = $i -> intro;
            $qtimelimit = $i -> timelimit;
        }
        $categorylist = $DB->get_records_sql('SELECT * from {course} where id = ?',array($course));
        foreach($categorylist as $i){
            $category = $i -> category;
        }
        $courselist = $DB->get_records_sql('SELECT * from {course_categories}');
        foreach($courselist as $i){
            $t = $i -> id;
            if ($t == $category){
                $coursename = $i -> name;
                $coursenamet = $i -> parent;
            }            
        }
        if($coursenamet != 0){
            foreach($courselist as $i){
                $t = $i -> id;
                if ($coursenamet == $t){
                    $coursename = $i -> name;
                }
            }
        }
        #$year = preg_replace("/*[0-9][0-9]/","",$coursename);
        $qgrade = sprintf('%.2f',$qgrade);
        $qtimelimit = (float)($qtimelimit/3600);        
        $qform = "<table style = \"border-collapse: collapse;width:95%;\">";
        $qform .= " <tr><th class = \"thleft\">Programme: B.Tech / M.Tech </th>
                    <th class = \"thright\">Semester:&nbsp;</th></tr>
                    <tr><th class = \"thleft\">Course Code:&nbsp;</th>
                    <th class = \"thright\">Course Name:&nbsp;$cname</th></tr>
                    <tr><th class = \"thleft\">Branch:&nbsp;$coursename</th>
                    <th class = \"thright\">Academic Year:&nbsp;</th></tr>
                    <tr><th class = \"thleft\">Duration:&nbsp;$qtimelimit Hours</th>
                    <th class = \"thright\">Max Marks:&nbsp;$qgrade</th></tr>
                    <tr><th class = \"thleft\">Student PRN No. </th>
                    <th class = \"thright\">
                    <table class = \"qformmis\"><tr><td class = \"qformmis\"></td><td class = \"qformmis\"></td>
                    <td class = \"qformmis\"></td><td class = \"qformmis\"></td><td class = \"qformmis\"></td>
                    <td class = \"qformmis\"></td><td class = \"qformmis\"></td><td class = \"qformmis\"></td>
                    <td class = \"qformmis\"></td><td class = \"qformmis\"></td><td class = \"qformmis\"></td></tr></table></th>
                    </tr></table>";
        $qform_count = substr_count($expout,$qform);
        #$expout .= $qform_count;
        if($qcount == 1){
            $expout .= "<h3 style = \"text-align:center\"><b>".$qname."</b></h3><br>";
            $expout .= $qform;
            if(preg_match('/Instructions:/',$qintro)  || preg_match("/instructions:/",$qintro)){
                $expout .= $qintro;
            }
            else{
                $expout .= "<h4 class = \"thleft\">Instructions:&nbsp;</h4>".$qintro;
            }
        }
        $expout .= "<hr class=\"new4\">";


        #$expout = "";
        $aout = "";
        $id = $question->id;
        #$expout .= $id;
        // Add comment and div tags.
        $expout .= "<!-- question: {$id}  name: {$question->name} -->\n";
        $expout .= "<div class=\"question\">\n";
        #$quizid = required_param('id', PARAM_INT); // Course Module ID, or ...




        // Selection depends on question type.
        switch($question->qtype) {
            case 'ordering': 
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                $tmpm = '';
                
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }
                    

                }

                $tmp = $question -> questiontext;
                $qmark = $DB->get_records('question_attempts', array("questionid"=>$id), 'id ASC');

                $tmp = b_wrap("{$tmp}");
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= $m;
                
                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";
                
                $id = $question->id;
                $orderans = $DB->get_records('question_answers', array("question"=>$id), 'fraction ASC');
                #$expout .= "<ol type=\"a\" class=\"ordering\">";
                $tmp = array();
                foreach($orderans as $i){
                    $tmp[] = $i -> answer;
                }
                shuffle($tmp);
                $c = 97;                
                foreach($tmp as $i){
                    $nc = chr($c);
                    $expout .= "&nbsp;&nbsp;&nbsp;$nc.&nbsp;".$i."<br>";
                    $c++;
                }
                #$expout .= "</ol>";

                $aout .= "Ans:<br>";
                #$aout .= "Ans:<ol type=\"1\" class=\"ordering\">";
                $tmp_ans = array();
                foreach ($orderans as $answer) {
                    $i = $answer -> fraction;
                    $i--;
                    $tmp_ans[$i] = $answer -> answer;
                    $tmp_ans[$i] = b_wrap("{$tmp_ans[$i]}");
                }
                $c = 1;
                foreach($tmp_ans as $i){
                    #$aout .=  "<li>$i</li>";
                    $aout .= "&nbsp;&nbsp;&nbsp;$c.&nbsp;".$i."<br>";
                    $c++;
                }        
                #$aout .= "</ol>";
                $aout .= "<br>";

                break;
            case 'calculatedmulti':
            case 'calculatedsimple':
            case 'calculated':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                $all_ans = $DB->get_records('question_datasets', array(), 'id ASC');
                $all_ans1 = $DB->get_records('question_dataset_definitions', array(), 'id ASC');
                $all_ans2 = $DB->get_records('question_dataset_items', array(), 'id ASC');
                $dd_def = [];
                $name = [];
                $value = [];
                $x = $question->id;
                
                foreach($all_ans as $i){
                    $y = $i -> question;
                    if($x == $y){
                        $dd_def[] = $i -> datasetdefinition;
                    }
                }
                foreach($all_ans1 as $i){
                    $y = $i -> id;
                    foreach($dd_def as $def){
                        if($y == $def){
                            $name[] = $i-> name;
                        }
                    }
                }
                foreach($all_ans2 as $i){
                    $y = $i -> definition;
                    foreach($dd_def as $def){
                        if($y == $def){
                            $value[] = $i-> value;
                        }
                    }
                }
            
                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");                  
                $tmp = b_wrap("{$tmp}");

                $ncount = count($name);
                $vcount = count($value);
                if($vcount != $ncount){
                    $ndv = (int)($vcount/$ncount);
                    $ndc = (int)($vcount/$ndv);
                    $c = random_int(0,$ndv-1);
                    $l = $ndc*$c;
                    $i = 0;
                    while($i < $l){
                        unset($value[$i]);
                        $i++;
                    }
                }
                $value = array_values($value);
                
                for($i=0;$i<count($name);$i++){
                    $x = "{".$name[$i]."}";
                    $y = $value[$i];
                    $tmp = str_replace($x,$y,$tmp);
                }
                                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";
                $aout .= "Ans: ";
                foreach ($question->options->answers as $answer) {
                    $answertext = $this->repchar( $answer->answer );
                    $answertext = (((float)($oldanswers[$answer->id]->fraction)) > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                    if((((float)($oldanswers[$answer->id]->fraction)) > 0.0)) {
                        $aout .= "Formula : ".$answertext;
                        
                    }
                }
                $tmp = $aout;
                $tmp1 = $aout;
                for($i=0;$i<count($name);$i++){
                    $x = "{".$name[$i]."}";
                    $y = $value[$i];
                    $tmp1 = str_replace($x,$y,$tmp1);
                }
                #$tmp1 = str_replace("Ans: Formula :",", where values",$tmp1);
                $tmp1 = str_replace("Ans: Formula :",", [ for example ",$tmp1);
                $tmp1 .= " ]";
                $aout = $tmp."&nbsp;&nbsp;".$tmp1."<br>"; 
                $aout .= "<br>";   
                break;
            case 'mtf':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                $mtfans = $DB->get_records('qtype_mtf_rows', array("questionid"=>$id), 'id ASC');
                $mtfans1 = $DB->get_records('qtype_mtf_columns', array("questionid"=>$id), 'id ASC');
                $res_txt = [];
                $num = [];
                foreach($mtfans1 as $mtf1){
                    $num[] = $mtf1->number;
                    $res_txt[] = $mtf1->responsetext;
                }
                $tmp = $question -> questiontext;
                $tmp = b_wrap("{$tmp}");
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                #$expout .= $m;

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                $expout .= "<table style = \"margin-left:3%;width:90%;border-collapse: collapse;border: 1px solid black;\">";
                $tc = 1;
                foreach($mtfans as $mtf){
                    $m = $mtf->optiontext;
                    $expout .= "<tr>
                                <td style = \"border-collapse: collapse;border: 1px solid black;width:5%;\"><b>$tc.</b></td>
                                <td style = \"border-collapse: collapse;border: 1px solid black;\"><b>$m</b></td>
                                <td style = \"border-collapse: collapse;border: 1px solid black;text-align:center;\">{$res_txt[0]}</td>
                                <td style = \"border-collapse: collapse;border: 1px solid black;text-align:center;\">{$res_txt[1]}</td>
                                </tr>";
                    $tc++;
                }
                $expout .= "</table>";

                $aout .= "Ans:<br>";
                $mtfans2 = $DB->get_records('qtype_mtf_weights', array("questionid"=>$id), 'id ASC');
                $tc = 1;
                foreach ($mtfans2 as $answer) {
                    $answertext = $answer->columnnumber;
                    #$aout .= $answertext;
                    for ($i=0; $i < count($num); $i++) {
                        if($num[$i] == $answertext){
                            $answertext = $res_txt[$i];
                        }
                    }
                    $ansfraction = $answer -> weight;
                    $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                    if($ansfraction > 0.0) {
                        $aout .= $tc.":&nbsp;&nbsp;".$answertext."<br>";
                        $tc++;
                    }
                }
                $aout .= "<br>";
                break;

            case 'truefalse':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                  
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                #$m = str_replace("<br>","\t",$m);
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";
                  
                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";
                
                #$expout .= "True/False";
                #$expout .= '<p class="questiontext">' . $tmp . "\n";

                $sttrue = get_string('true', 'qtype_truefalse');
                $stfalse = get_string('false', 'qtype_truefalse');
                /*$expout .= "<ul type=\"A\" class=\"truefalse\">";
                $expout .= "  <li id=\"quest_{$id}\">{$sttrue}</li>";
                $expout .= "  <li id=\"quest_{$id}\">{$stfalse}</li>";
                $expout .= "</p></ul>\n";*/

                $c = 65;
                $i = chr($c);
                $expout .= "&nbsp;&nbsp;$i.&nbsp;".$sttrue."<br>";
                $c++;
                $i = chr($c);
                $expout .= "&nbsp;&nbsp;$i.&nbsp;".$sttrue."<br>";
                


                $aout .= "Ans:";
                foreach ($question->options->answers as $answer) {
                    $answertext = $this->repchar( $answer->answer );
                    $answertext = (((float)($oldanswers[$answer->id]->fraction)) > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                    if((((float)($oldanswers[$answer->id]->fraction)) > 0.0)) {
                        $aout .= $answertext;
                    }
                }
                $aout .= "<br><br>";
                break;
            case 'multichoice':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                #$expout .= "<h3>{$question->name}</h3>\n";
                
                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                
                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                #$m = str_replace("<br>","\t",$m);                
                
                $pos = strrpos($tmp, "<br>");
                if($pos !== false){
                    $tmp = substr_replace($tmp, "", $pos, strlen("<br>"));
                }
                $pos = strrpos($tmp, "<br>");
                if($pos !== false){
                    $tmp = substr_replace($tmp, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";
                #$tmp .= "<br><br>";
                $expout .= "<table style=\"width:100%;margin-bottom:10px;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%;text-align:left;\">$tmp<br>";
                if($question->qtype == 'multichoice' && !$question->options->single) {
                    $expout .= "(Multiple Options Correct)</td>";    
                }
                else{
                    $expout .= "</td>";
                }
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                if($question->qtype == 'multichoice' && !$question->options->single) {
                    #$expout .= "<div>(Multiple Options Correct)</div>";
                    $aout .= "Ans:<table>";
                }
                else{
                    $aout .= "Ans:";
                }
                #$expout .= "<p class=\"ex2\">{$tmpm}</p></div>";
                #$expout .= '<p class="questiontext">' . $tmp . "\n";

                #$expout .= "<ol type=\"A\" class=\"multichoice\">";
                $c = 65;
                $correct_opt_count = 0;
                $wrong_op = 0;
                foreach ($question->options->answers as $answer) {
                    if(((float)($oldanswers[$answer->id]->fraction)) > 0.0){
                        $correct_opt_count++;
                    }
                    else{
                        $wrong_op++;
                    }
                }
                foreach ($question->options->answers as $answer) {
                    $nc = chr($c);
                    $answertext = $this->repchar( $answer->answer );
                    $ansfraction = $this->repchar( $answer->fraction );
                    $ansfraction = sprintf('%.2f',$ansfraction);
                    $answertext = (((float)($oldanswers[$answer->id]->fraction)) > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                    
                    if($question->qtype == 'multichoice' && !$question->options->single) {
                        if((((float)($oldanswers[$answer->id]->fraction)) > 0.0)) {
                            $ansfraction = (float)$tmpm/(float)$correct_opt_count;
                            $ansfraction = sprintf('%.2f',$ansfraction);
                            $aout .= "<tr><td>$nc.</td><td>$answertext</td><td>[$ansfraction marks]</td></tr>";
                            #$aout .= $answertext."".$ansfraction;
                            #$aout .= $ansfraction;
                        }
                        else{
                            $ansfraction = (float)$tmpm/(float)$wrong_op; 
                            $ansfraction = sprintf('%.2f',$ansfraction);
                            $aout .= "<tr><td>$nc.</td><td>$answertext</td><td>[-$ansfraction marks]</td></tr>";
                        }
                    }
                    else{
                        if((((float)($oldanswers[$answer->id]->fraction)) > 0.0)){
                            $aout .= $answertext;
                        }
                    }
                    
                    $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                    $answer->answerformat, array('para' => false, 'newlines' => false)));

                    $answertext = break_wrap($answertext);                   
                    #$expout .= "<li id=\"quest_{$id}\">{$answertext}</li>";
                    $expout .= "&nbsp;&nbsp;$nc.&nbsp;".$answertext."<br>";                    
                    $c++;
                }
                if($question->qtype == 'multichoice' && !$question->options->single) {
                    $aout .= "</table>";                
                }
                
                
                $expout .= "</p></ol>\n";
                $aout .= "<br>";
                

                break;

            case 'ddwtos':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                $tmp = preg_replace("[[[*[0-9]]]]", '', $tmp);
                $tmp = str_replace(array('[[',']]'),'_______',$tmp);
                
                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;top-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                #$expout .= "<ol type=\"A\" class=\"ddwtos\">";
               $answertextlist = array();
                foreach ($question->options->answers as $answer) {
                   
                    $answertext = $this->repchar( $answer->answer );
                    
                    $answertext = (((float)($oldanswers[$answer->id]->fraction)) > 0.0);
                    
                    $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                    $answer->answerformat, array('para' => false, 'newlines' => false)));

                    $answertextlist[] = break_wrap($answertext);
                    #$expout .= "<li id=\"quest_{$id}\">{$answertext}</li></p>";                    
                }
                shuffle($answertextlist);
                $c = 65;
                foreach($answertextlist as $i){
                    $nc = chr($c);
                    $expout .= "&nbsp;&nbsp;$nc.&nbsp;".$i."<br>";
                    $c++;
                }
                $expout .= "</ol>\n";

                $aout .= "Ans:";
                $gapans = $DB->get_records('question_attempts', array("questionid"=>$question->id), 'id ASC');
                $c = 0;
                foreach ($gapans as $a) {
                    if($c == 0){
                        $answertext = $a->rightanswer ;
                        $aout .= "<b>$answertext</b><br>";
                        $aout = str_replace(array('{','}'),'',$aout);
                        $aout = str_replace(' ','<br>',$aout);
                        $c++;
                    }                                                  
                }
                $aout .= "<br>";
                break;
            case 'gapselect':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                $tmp = preg_replace("[[[*[0-9]]]]", '', $tmp);
                $tmp = str_replace(array('[[',']]'),'_______',$tmp);
                    
                
                    
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";
                    
                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";
                    
                #$expout .= "<ol type=\"A\" class=\"ddwtos\">";
               
                $answertextlist = array();
                foreach ($question->options->answers as $answer) {
                    
                    $answertext = $this->repchar( $answer->answer );
                        
                    $answertext = (((float)($oldanswers[$answer->id]->fraction)) > 0.0);
                    
                    $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                    $answer->answerformat, array('para' => false, 'newlines' => false)));

                    $answertextlist[] = break_wrap($answertext);
                    #$expout .= "<li id=\"quest_{$id}\">{$answertext}</li></p>";
                        
                }
                shuffle($answertextlist);
                $c = 65;
                foreach($answertextlist as $i){
                    $nc = chr($c);
                    $expout .= "&nbsp;&nbsp;$nc.&nbsp;".$i."<br>";
                    $c++;
                }
                $expout .= "</ol>\n";
                $aout .= "Ans:";
                $gapans = $DB->get_records('question_attempts', array("questionid"=>$question->id), 'id ASC');
                $c = 0;
                foreach ($gapans as $a) {
                    if($c == 0){
                        $answertext = $a->rightanswer ;
                        $aout .= "<b>$answertext</b><br>";
                        $aout = str_replace(array('{','}'),'',$aout);
                        $aout = str_replace(' ','<br>',$aout);
                        $c++;
                    }                                                  
                }
                $aout .= "<br>";
                break;    

            case 'shortanswer':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                
                
                $tmp = str_replace("<table>","<table class = \"qtable\" style = \"width: 90%;\">",$tmp);
                $tmp = preg_replace("/<th[^<]*>/","<th class = \"qtable\">",$tmp);
                $tmp = preg_replace("/<td[^<]*>/","<td class = \"qtable\">",$tmp); 

                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";

                /*$expout .= html_writer::start_tag('ul', array('class' => 'shortanswer'));
                $expout .= html_writer::start_tag('li');
                #$expout .= html_writer::label(get_string('answer'), 'quest_'.$id, false, array('class' => 'accesshide'));
                #$expout .= html_writer::empty_tag('input', array('id' => "quest_{$id}", 'name' => "quest_{$id}", 'type' => 'text'));
                $expout .= html_writer::end_tag('li');
                $expout .= html_writer::end_tag('ul');
                */
                $aout .= "Ans:";
                foreach ($question->options->answers as $answer) {
                    $answertext = $this->repchar( $answer->answer );
                    $answertext = (((float)($oldanswers[$answer->id]->fraction)) > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                    if((((float)($oldanswers[$answer->id]->fraction)) > 0.0)) {
                        $aout .= $answertext;
                    }
                }
                $aout .= "<br><br>";
                break;

            case 'numerical':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                
                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";

                /*$expout .= html_writer::start_tag('ul', array('class' => 'numerical'));
                $expout .= html_writer::start_tag('li');
                #$expout .= html_writer::label(get_string('answer'), 'quest_'.$id, false, array('class' => 'accesshide'));
                #$expout .= html_writer::empty_tag('input', array('id' => "quest_{$id}", 'name' => "quest_{$id}", 'type' => 'text'));
                $expout .= html_writer::end_tag('li');
                $expout .= html_writer::end_tag('ul');
                */
                $aout .= "Ans: ";
                foreach ($question->options->answers as $answer) {
                    $answertext = $this->repchar( $answer->answer );
                    $answertext = (((float)($oldanswers[$answer->id]->fraction)) > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                    if((((float)($oldanswers[$answer->id]->fraction)) > 0.0)) {
                        $aout .= $answertext;
                    }
                }
                $aout .= "<br><br>";
                break;
            
            case 'match':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                } 

               # $questiondata->options->subquestions = $DB->get_records('question_match_sub',
                #array('question' => $question->id), 'id ASC');

                

                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                
                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";
                
                $expout .= html_writer::start_tag('ul', array('class' => 'match'));

                // Build answer list.
                $answerlist = array();
                foreach ($question->options->subquestions as $subquestion) {
                    $answerlist[] = $this->repchar( $subquestion->answertext );
                    $questionlist[] = $this->repchar( $subquestion->questiontext);
                }
                shuffle( $answerlist ); // Random display order.
                for ($x = 0; $x < count($questionlist); $x++){
                    $questionlist[$x] = pmatch($questionlist[$x]);
                }
                    // Build select options.
               /* $selectoptions = array();
                foreach ($answerlist as $ans) {
                    $selectoptions[s($ans)] = s($ans);
                }*/
        
                // Display.
                $option = 0;
                #$expout .= html_writer::tag('ul', array('class' => 'match'));
                $expout .= "<table class = \"b\" style = \"width:90%\">";
                
                $len = (count($answerlist) > count($questionlist)) ? count($answerlist):count($questionlist);
                for ($x = 0; $x < $len; $x++){
                    if($x==0){
                        $expout .= "<tr>";
                        $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\"><b><i>Questions</b></i></td>";
                        $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\"><b><i>Answers</b></i></td>";
                        $expout .= "/<tr>";
                    }
                    $expout .= "<tr>";
                    if ($questionlist[$x] != ''){
                        $t = $x + 1;
                        $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\">"."{$t}. " .$questionlist[$x] . "</td>";
                    }
                    else{
                        $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\"></td>";
                    }
                    $t = $x + 1;
                    $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\">"."{$t}. " .$answerlist[$x] . "</td>";
                    $expout .= "/<tr>";
                }
                $expout .= "</table>";
                $expout .= html_writer::end_tag('ul');

                $matchans = $DB->get_records('qtype_match_subquestions', array(), 'id ASC');
                #$matchans = $DB->get_records_sql('SELECT questiontext,answertext FROM {qtype_match_subquestions}',array('questionid' => $question->id),'id ASC');
                $q_match = [];
                $a_match = [];
                #$aout .=  $matchans[7]->questiontext;
                #$aout .= count($matchans);

                $id_question = $question->id;
                for ($x = 1; $x < count($matchans); $x++){
                    $id_subquestion = $matchans[$x]->questionid;  
                    if($id_question == $id_subquestion){
                        $t1 = $matchans[$x]->questiontext;
                        $t1 = pmatch($t1);
                        $t1 = str_replace("<br>","",$t1);
                        $q_match[] = $t1;
                        $t1 = $matchans[$x]->answertext;
                        $t1 = pmatch($t1);
                        $t1 = str_replace("<br>","",$t1);
                        $a_match[] = $t1;
                    }
                }
               
                $aout .= "Ans: ";
                $aout .= "<table>";
                for ($x = 0; $x < count($q_match); $x++){
                    if($q_match[$x] != ""){
                        $aout .= "<tr>";
                        $aout .= "<td><b>$q_match[$x]</b></td><td>=</td><td><b>$a_match[$x]</b></td></tr>";    
                        #$aout .= "<td><b>$q_match[$x]</b>:<b>$a_match[$x]</b></td></tr>";    
                    }
                }
                $aout .= "</table>";
                $aout .= "<br>";
                break;
            case 'description':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }


                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                
                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";
                
                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";
                
                #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";

                $aout .= "Ans: ";
                foreach ($question->options->answers as $answer) {
                    $answertext = $this->repchar( $answer->answer );
                    $answertext = (((float)($oldanswers[$answer->id]->fraction)) > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                    if((((float)($oldanswers[$answer->id]->fraction)) > 0.0)) {
                        $aout .= $answertext;
                    }
                }
                $aout .= "<br><br>";
                break;

            case 'multianswer':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                /*$multians = $DB->get_records('question_multianswer', array('question' => $question->id), 'id ASC');
                $seq = $multians->id;
                $expout .= $seq;*/
                
                // Format and add the question text.
                
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $multique = $DB->get_records('question_multianswer', array("question" => $question->id), 'id ASC');
                $id = $question->id;
                foreach ($multique as $answer) {
                    $t = $answer->question;
                    if($t == $id){
                        $seq = $answer->sequence;
                    }
                }
                $seq = explode("," ,$seq);
                $c = count($seq)-1;
                    
                $multiop = $DB->get_records_sql('SELECT * from {question} where parent = ?',array($question->id), 'id ASC');
                $tmp = str_replace("<pre>","<pre><b>",$tmp);
                $tmp = str_replace("</pre>","</b></pre>",$tmp);
                foreach($multiop as $i){
                    $t1 = $i -> questiontext;
                    $t1 = "______      ".$t1;                    
                    $tmp = preg_replace("/{#*[0-9]}/",$t1,$tmp,1);
                }
                
                
                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:auto%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";

                $aout .= "Ans: ";

                $multians = $DB->get_records_sql('SELECT * from {question_answers} where question >= ? and question <= ?' ,array($seq[0],$seq[$c]), 'fraction ASC');
                $countans = 0;
                    foreach ($multians as $answer) {
                        $answertext = $answer->answer;
                        $ansfraction = $answer->fraction;
                        $ansfraction = sprintf('%.2f',$ansfraction);
                        $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                        if($ansfraction > 0.0) {
                            $countans++;
                            if($ansfraction != 1.0){
                                $countans--;    
                                $aout .= $countans.".".$answertext."&nbsp;:".$ansfraction."&nbsp;&nbsp;";
                                                    
                            }
                            else {
                                $aout .= $countans.".".$answertext."&nbsp;&nbsp;";
                            
                            }
                         
                            $aout .= "<br>";
                        }
                    }              
                    $aout .= "<br>";
                break;

            case 'select missing word':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                $tmp = preg_replace("[[[*[0-9]]]]", '', $tmp);
                $tmp = str_replace(array('[[',']]'),'_______',$tmp);
                
                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";

                $aout .= "Ans: ";
                $gapans = $DB->get_records('question_attempts', array("questionid"=>$question->id), 'id ASC');
                $c = 0;
                foreach ($gapans as $a) {
                    if($c == 0){
                        $answertext = $a->rightanswer ;
                        $aout .= "<b>$answertext</b><br>";
                        $aout = str_replace(array('{','}'),'',$aout);
                        $aout = str_replace(' ','<br>',$aout);
                        $c++;
                    }                                                  
                }
                $aout .= "<br>";
                break;

            case 'essay':
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionid = ?',array($question -> id));
                foreach($quizmark as $i){
                    $t = $i -> questionusageid;
                    if ($t == $uniqueid){
                        $tmpm = $i -> maxmark;
                        $tmpm = sprintf('%.2f',$tmpm);
                    }

                }

                // Format and add the question text.
                $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                
                
                $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                $pos = strrpos($m, "<br>");
                if($pos !== false){
                    $m = substr_replace($m, "", $pos, strlen("<br>"));
                }
                #$expout .= "$m";

                $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                $expout .= "<td style=\"width:90%\;text-align:left;\">$tmp</td>";
                $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                $expout .= "</tr></table>";

                #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";

                $aout .= "Ans: ";
                $all_ans = $DB->get_records('qtype_essay_options', array("questionid" => $question->id), 'id ASC');
                foreach ($all_ans as $answer) {
                    $answertext = $answer->graderinfo;
                    $answertext = b_wrap("{$answertext}");
                    $aout .= $answertext;
                }
                $aout .= "<br><br>";
                break;

            case 'random':

                $randomans = $DB->get_records('question', array(), 'id ASC');
                $idr = $question->id;
                $foundq = [];
                $foundm = [];
                $foundid = [];
                $qtype = [];
                $category = '';
                foreach($randomans as $check){
                    $tmpr = $check->id;
                    if ($idr == $tmpr) {
                        $category = $check->category;
                    }
                }
                
                foreach($randomans as $check){
                    $tmpr = $check->category;
                    if ($tmpr == $category) {                        
                        $t = $check->questiontext;
                        if($t != '0'){
                            $foundq[] = $t;
                            $foundm[] = $check->defaultmark;
                            $foundid[] = $check->id;
                            $qtype[] = $check->qtype;
                        }                        
                    }
                }

                
                #$choice = random_int(1,(count($foundid)-1));
                $choice = -1;
                $rqc = 97;
                $or_count = count($qtype);
                $or_count--;                
                $tmpm = '';
                $uniqueid = '';
                $quizes = $DB->get_records_sql('SELECT * from {quiz_attempts} where quiz = ?',array($quizid));
                foreach($quizes as $i){
                    $uniqueid = $i -> uniqueid;
                }
                $quizmark = $DB->get_records_sql('SELECT * from {question_attempts} where questionusageid = ?',array($uniqueid));
                foreach($quizmark as $i){
                    $t = $i -> questionid;
                    foreach($foundid as $j){
                        if ($j == $t){
                            $tmpm = $i -> maxmark;
                            $tmpm = sprintf('%.2f',$tmpm);

                        }
                    }                    
                }
                $lastor = 0;
                foreach ($qtype as $qtype_r) {
                    if ($choice == -1){
                        $expout .= "<table style=\"width:100%;margin-left:-30px;margin-right:-35px;\"><tr>";
                        $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$qcount."."</td>";
                        $expout .= "<td style=\"width:90%\;text-align:left;\"><b>select any one:</b></td>";
                        $expout .= "<td style=\"width:20%\;text-align:right;vertical-align: top;\"><font color=\"navy\">[$tmpm marks]</font></td>";
                        $expout .= "</tr></table>";
                        
                    } 
                    $choice++;
                    $rqn = chr($rqc);
                    $qtype_r = $qtype[$choice];
                    switch ($qtype_r) {                                                                    
                        case 'match':
                            $rqc++;
                            $tmp = $foundq[$choice];
                            $tmpm = $foundm[$choice];
                            $tmpm = sprintf('%.2f',$tmpm);
                            $ansid = $foundid[$choice];
                            $tmp = b_wrap("{$tmp}");
                            #$expout .= $tmp;
                        
                            $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                            #$m = str_replace("<br>","\t",$m);                
                        
                            $pos = strrpos($m, "<br>");
                            if($pos !== false){
                                $m = substr_replace($m, "", $pos, strlen("<br>"));
                            }
                            #$expout .= "$m";
                            $expout .= "<table style=\"width:90%;margin-left:-15px;margin-right:-35px;\"><tr>";
                            $expout .= "<td style=\"width:3%;text-align:left;vertical-align: top;\">$rqn."."</td>";
                            $expout .= "<td style=\"width:100%\;text-align:left;\">$tmp</td>";                            
                            $expout .= "</tr></table>";

                            $expout .= html_writer::start_tag('ul', array('class' => 'match'));

                            // Build answer list.
                            $answerlist = array();
                            $questionlist = array();
                            $matchans = $DB->get_records('qtype_match_subquestions', array("questionid" => $foundid[$choice]), 'id ASC');
                            foreach ($matchans as $subquestion) {
                                $answerlist[] = $subquestion->answertext;
                                $questionlist[] = $subquestion->questiontext;
                            }
                            $a_match = array();
                            $q_match = array();
                            $a_match = $answerlist;
                            $q_match = $questionlist;
                            shuffle( $answerlist ); // Random display order.
                            for ($x = 0; $x < count($questionlist); $x++){
                                $questionlist[$x] = pmatch($questionlist[$x]);
                            }
                            $expout .= "<table class = \"b\" style = \"width:90%\">";
                
                            $len = (count($answerlist) > count($questionlist)) ? count($answerlist):count($questionlist);
                            for ($x = 0; $x < $len; $x++){
                                if($x==0){
                                    $expout .= "<tr>";
                                    $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\"><b><i>Questions</b></i></td>";
                                    $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\"><b><i>Answers</b></i></td>";
                                    $expout .= "/<tr>";
                                }
                                $expout .= "<tr>";
                                if ($questionlist[$x] != ''){
                                    $t = $x + 1;
                                    $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\">"."{$t}. " .$questionlist[$x] . "</td>";
                                }
                                else{
                                    $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\"></td>";
                                }
                                $t = $x + 1;
                                $expout .= "<td class= \"match\" style = \"width:(100/x)%;white-space:nowrap;\">"."{$t}. " .$answerlist[$x] . "</td>";
                                $expout .= "/<tr>";
                            }
                            $expout .= "</table>";
                            $expout .= html_writer::end_tag('ul');
                           
                            $aout .= "<br>Ans:<b>($rqn) </b>";
                            $aout .= "<table>";
                            for ($x = 0; $x < count($q_match); $x++){
                                if($q_match[$x] != ""){
                                    $aout .= "<tr>";
                                    $aout .= "<td><b>$q_match[$x]</b></td><td>=</td><td><b>$a_match[$x]</b></td></tr>";    
                                    #$aout .= "<td><b>$q_match[$x]</b>:<b>$a_match[$x]</b></td></tr>";    
                                }
                            }
                            $aout .= "</table>";                            
                            if($choice != $or_count){
                                $expout .= "<div style=\"text-align:center\"><b>OR</b></div>";
                            }
                            break;
                        case 'truefalse':
                            $rqc++;
                            $tmp = $foundq[$choice];
                            $tmpm = $foundm[$choice];
                            $tmpm = sprintf('%.2f',$tmpm);
                            $ansid = $foundid[$choice];
                            $tmp = b_wrap("{$tmp}");
                            #$expout .= $tmp;
                        
                            $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                            #$m = str_replace("<br>","\t",$m);                
                        
                            $pos = strrpos($m, "<br>");
                            if($pos !== false){
                                $m = substr_replace($m, "", $pos, strlen("<br>"));
                            }
                            #$expout .= "$m";
                            $expout .= "<table style=\"width:90%;margin-left:-15px;margin-right:-35px;\"><tr>";
                            $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$rqn."."</td>";
                            $expout .= "<td style=\"width:100%\;text-align:left;\">$tmp</td>";
                            $expout .= "</tr></table>";
                            
                            $sttrue = get_string('true', 'qtype_truefalse');
                            $stfalse = get_string('false', 'qtype_truefalse');
                            $expout .= "<ul type=\"A\" class=\"truefalse\">";
                            $expout .= "  <li id=\"quest_{$id}\">{$sttrue}</li>";
                            $expout .= "  <li id=\"quest_{$id}\">{$stfalse}</li>";
                            $expout .= "</ul>\n";
            
                            $aout .= "<br>Ans:<b>($rqn) </b>";
                            $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                            foreach ($randomans1 as $answer) {
                                $answertext =$answer->answer;
                                $ansfraction = $answer->fraction;
                                $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                                if($ansfraction > 0.0) {
                                    $aout .= $answertext;
                                }
                            }                            
                            if($choice != $or_count){
                                $expout .= "<div style=\"text-align:center\"><b>OR</b></div>";
                            }                            
                            $aout .= "<br>";
                            break;
    
                        case 'multichoice':
                            $rqc++;
                            $tmp = $foundq[$choice];
                            $tmpm = $foundm[$choice];
                            $tmpm = sprintf('%.2f',$tmpm);
                            $ansid = $foundid[$choice];
                            $tmp = b_wrap("{$tmp}");
                            #$expout .= $tmp;
                        
                            $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                            #$m = str_replace("<br>","\t",$m);                
                        
                            $pos = strrpos($m, "<br>");
                            if($pos !== false){
                                $m = substr_replace($m, "", $pos, strlen("<br>"));
                            }
                            #$expout .= "$m";
                            $expout .= "<table style=\"width:90%;margin-left:-15px;margin-right:-35px;\"><tr>";
                            $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$rqn."."</td>";
                            $expout .= "<td style=\"width:100%\;text-align:left;\">$tmp</td>";
                            $expout .= "</tr></table>";

            
                            if($question->qtype == 'multichoice' && !$question->options->single) {
                                $expout .= "<div>(Multiple Options Correct)</div>";
                                $aout .= "Ans:<b>($rqn)</b><table>";
                            }
                            else{
                                $aout .= "<br>Ans:<b>($rqn) </b>";
                            }
        
                            $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');
        
        
                            #$expout .= "<p class=\"ex2\">{$tmpm}</p></div>";
                            #$expout .= '<p class="questiontext">' . $tmp . "\n";
        
                            $expout .= "<ol type=\"A\" class=\"multichoice\">";
                            $wrong_op = 0;
                            $correct_opt_count = 0;
                            foreach ($randomans1 as $answer) {
                                $ansfraction = $answer->fraction;
                                if ($ansfraction > 0.0){
                                    $correct_opt_count++;
                                }
                                else{
                                    $wrong_op++;
                                }
                            }
                            
                            foreach ($randomans1 as $answer) {
                                $answertext = $answer->answer;
                                $ansfraction = $answer->fraction;
                                $answertext = b_wrap("{$answertext}");
                            
                                if($qtype_r == 'multichoice' && $correct_opt_count != 1) {
                                    if($ansfraction > 0.0) {
                                        $ansfraction = (float)$tmpm/(float)$correct_opt_count;
                                        $ansfraction = sprintf('%.2f',$ansfraction);                                        
                                        $tmp = "$answertext : [ $ansfraction  marks ]";
                                        $aout .= str_replace("<br>","",$tmp);
                                        $aout .= "<br>";
                                                                                                                        
                                    }
                                    else{
                                        $answertext = str_replace(array('<b>','</b>'),"",$answertext); 
                                        $ansfraction = (float)$tmpm/(float)$wrong_op;
                                        $ansfraction = sprintf('%.2f',$ansfraction);
                                        $tmp = "$answertext : [ -$ansfraction  marks ]";
                                        $aout .= str_replace("<br>","",$tmp);
                                        $aout .= "<br>";
                                    }
                                }
                                else{                                
                                    if($ansfraction > 0.0){
                                        $aout .= $answertext;
                                    }
                                }
                            
                                $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                                $answer->answerformat, array('para' => false, 'newlines' => false)));
                           
                                $answertext = break_wrap($answertext);
                            
                                
                                $expout .= "<li id=\"quest_{$id}\">{$answertext}</li>";                        
                                
                            }
                            if($question->qtype == 'multichoice' && !$question->options->single) {
                                $aout .= "</table>";                
                            }
                            
                            if($choice != $or_count){
                                $expout .= "<div style=\"text-align:center\"><b>OR</b></div>";
                            }  
                            $expout .= "</ol>\n";                                                      
                            
                            break;
    
                        case "ddwtos":
                            $rqc++;
                            $tmp = $foundq[$choice];
                            $tmpm = $foundm[$choice];
                            $tmpm = sprintf('%.2f',$tmpm);
                            $ansid = $foundid[$choice];
                            $tmp = b_wrap("{$tmp}");
                            #$expout .= $tmp;
                            $tmp = preg_replace("[[[*[0-9]]]]", '', $tmp);
                            $tmp = str_replace(array('[[',']]'),'_______',$tmp);
                            $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                            #$m = str_replace("<br>","\t",$m);                
                        
                            $pos = strrpos($m, "<br>");
                            if($pos !== false){
                                $m = substr_replace($m, "", $pos, strlen("<br>"));
                            }
                            #$expout .= "$m";
                            $expout .= "<table style=\"width:90%;margin-left:-15px;margin-right:-35px;\"><tr>";
                            $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$rqn."."</td>";
                            $expout .= "<td style=\"width:100%\;text-align:left;\">$tmp</td>";
                            $expout .= "</tr></table>";

                            $expout .= "<ol type=\"A\" class=\"ddwtos\">";
                            $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                            foreach ($randomans1 as $answer) {
                                $answertext = $answer->answer;
                                $ansfraction = $answer->fraction;                      
                                
                                $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                                $answer->answerformat, array('para' => false, 'newlines' => false)));
            
                                $answertext = break_wrap($answertext);
                                $answertext = str_replace("<br>","",$answertext);
                                $expout .= "<li id=\"quest_{$id}\">{$answertext}</li>";
                            }
                            $expout .= "</ol>\n";
        
                            $aout .= "<br>Ans:<b>($rqn) </b>";
                            $gapans_r = $DB->get_records('question_attempts', array("questionid"=>$foundid[$choice]), 'id ASC');
                            $c_r = 0;
                            foreach($gapans_r as $i){
                                if($c_r == 0){
                                    $answertext_r = $i->rightanswer ;
                                    $tmp = "<b>$answertext_r</b><br>";
                                    $tmp = str_replace(array('{','}'),'',$tmp);
                                    $tmp = str_replace(' ','<br>',$tmp);
                                    $c_r++;
                                }
                                
                            }
                            if ($tmp == NULL){
                                foreach ($randomans1 as $answer) {
                                    $answertext =$answer->answer;
                                    $ansfraction = $answer->fraction;
                                    $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                                    if($ansfraction > 0.0) {
                                        $aout .= $answertext;
                                    }
                                }
                                $aout .= "<br>";
                            }
                            else{
                                $aout .= $tmp;
                            }                                                                                                            
        
                            if($choice != $or_count){
                                $expout .= "<div style=\"text-align:center\"><b>OR</b></div>";
                            }                            
                             
                             
                            break;
                        case "select missing word":
                        case "gapselect":
                            $rqc++;
                            $tmp = $foundq[$choice];
                            $tmpm = $foundm[$choice];
                            $tmpm = sprintf('%.2f',$tmpm);
                            $ansid = $foundid[$choice];
                            $tmp = b_wrap("{$tmp}");
                            #$expout .= $tmp;
                            $tmp = preg_replace("[[[*[0-9]]]]", '', $tmp);
                            $tmp = str_replace(array('[[',']]'),'_______',$tmp);
                            $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                            #$m = str_replace("<br>","\t",$m);                
                        
                            $pos = strrpos($m, "<br>");
                            if($pos !== false){
                                $m = substr_replace($m, "", $pos, strlen("<br>"));
                            }
                            #$expout .= "$m";
                            $expout .= "<table style=\"width:90%;margin-left:-15px;margin-right:-35px;\"><tr>";
                            $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$rqn."."</td>";
                            $expout .= "<td style=\"width:100%\;text-align:left;\">$tmp</td>";
                            $expout .= "</tr></table>";

                            $expout .= "<ol type=\"A\" class=\"ddwtos\">";
                            $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                            foreach ($randomans1 as $answer) {
                                $answertext = $answer->answer;
                                $ansfraction = $answer->fraction;                      
                                
                                $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                                $answer->answerformat, array('para' => false, 'newlines' => false)));
            
                                $answertext = break_wrap($answertext);
                                $expout .= "<li id=\"quest_{$id}\">{$answertext}</li>";
                            }
                            $expout .= "</ol>\n";
        
                            $aout .= "<br>Ans:<b>($rqn) </b>";
                            


                            $gapans_r = $DB->get_records('question_attempts', array("questionid"=>$foundid[$choice]), 'id ASC');
                            $c_r = 0;
                            foreach($gapans_r as $i){
                                if($c_r == 0){
                                    $answertext_r = $i->rightanswer ;
                                    $tmp = "<b>$answertext_r</b><br>";
                                    $tmp = str_replace(array('{','}'),'',$tmp);
                                    $tmp = str_replace(' ','<br>',$tmp);
                                    $c_r++;
                                }
                                
                            }
                            if ($tmp == NULL){
                                foreach ($randomans1 as $answer) {
                                    $answertext =$answer->answer;
                                    $ansfraction = $answer->fraction;
                                    $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                                    if($ansfraction > 0.0) {
                                        $aout .= $answertext;
                                    }
                                }
                                $aout .= "<br>";
                            }
                            else{
                                $aout .= $tmp;
                            }                                                                                                            
                                        
                            if($choice != $or_count){
                                $expout .= "<div style=\"text-align:center\"><b>OR</b></div>";
                            }                            
                              
                            break;
    
                        case "essay":                            
                        case "shortanswer":
                        case "numerical":
                        case "description": 
                            $rqc++;   
                            $tmp = $foundq[$choice];
                            $tmpm = $foundm[$choice];
                            $tmpm = sprintf('%.2f',$tmpm);
                            $ansid = $foundid[$choice];
                            $tmp = b_wrap("{$tmp}");
                            #$expout .= $tmp;
                            
                            $tmp = str_replace("<table>","<table class = \"qtable\" style = \"width: 90%;\">",$tmp);
                            $tmp = preg_replace("/<th[^<]*>/","<th class = \"qtable\">",$tmp);
                            $tmp = preg_replace("/<td[^<]*>/","<td class = \"qtable\">",$tmp); 
            
                            $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                            #$m = str_replace("<br>","\t",$m);                
                        
                            $pos = strrpos($m, "<br>");
                            if($pos !== false){
                                $m = substr_replace($m, "", $pos, strlen("<br>"));
                            }
                            #$expout .= "$m";
                            $expout .= "<table style=\"width:90%;margin-left:-15px;margin-right:-35px;\"><tr>";
                            $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$rqn."."</td>";
                            $expout .= "<td style=\"width:100%\;text-align:left;\">$tmp</td>";
                            $expout .= "</tr></table>";

                           
                            $aout .= "<br>Ans:<b>($rqn) </b>";
                            $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                            foreach ($randomans1 as $answer) {
                                $answertext =$answer->answer;
                                $ansfraction = $answer->fraction;
                                $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                                if($ansfraction > 0.0) {
                                    $aout .= $answertext;
                                }
                            }       
                            if($choice != $or_count){
                                $expout .= "<div style=\"text-align:center\"><b>OR</b></div>";
                            }                            
                            $aout .= "<br>"; 
                             
                            break;
                        case "calculated":
                        case "calculatedsimple":                    
                        case "calculatedmulti":
                            $rqc++;                            
                            $all_ans = $DB->get_records('question_datasets', array(), 'id ASC');
                            $all_ans1 = $DB->get_records('question_dataset_definitions', array(), 'id ASC');
                            $all_ans2 = $DB->get_records('question_dataset_items', array(), 'id ASC');
                            $dd_def = [];
                            $name = [];
                            $value = [];
                            $x = $foundid[$choice];
                        
                            foreach($all_ans as $i){
                                $y = $i -> question;
                                if($x == $y){
                                    $dd_def[] = $i -> datasetdefinition;
                                }
                            }
                            foreach($all_ans1 as $i){
                                $y = $i -> id;
                                foreach($dd_def as $def){
                                    if($y == $def){
                                        $name[] = $i-> name;
                                    }
                                }
                            }
                            foreach($all_ans2 as $i){
                                $y = $i -> definition;
                                foreach($dd_def as $def){
                                    if($y == $def){
                                        $value[] = $i-> value;
                                    }
                                }
                            }
                        
                            // Format and add the question text.
                            $tmp = $foundq[$choice];
                            $tmpm = $foundm[$choice];
                            $tmpm = sprintf('%.2f',$tmpm);
                            $ansid = $foundid[$choice];
                            $tmp = b_wrap("{$tmp}");

                            $ncount = count($name);
                            $vcount = count($value);
                            if($vcount != $ncount){
                                $ndv = (int)($vcount/$ncount);
                                $ndc = (int)($vcount/$ndv);
                                $c = random_int(0,$ndv-1);
                                $l = $ndc*$c;
                                $i = 0;
                                while($i < $l){
                                    unset($value[$i]);
                                    $i++;
                                }
                            }
                            $value = array_values($value);
                            
                            for($i=0;$i<count($name);$i++){
                                $x = "{".$name[$i]."}";
                                $y = $value[$i];
                                $tmp = str_replace($x,$y,$tmp);
                            }
                            
                            $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                            $pos = strrpos($m, "<br>");
                            if($pos !== false){
                                $m = substr_replace($m, "", $pos, strlen("<br>"));
                            }
                            #$expout .= "$m";
                            $expout .= "<table style=\"width:90%;margin-left:-15px;margin-right:-35px;\"><tr>";
                            $expout .= "<td style=\"width:3%;text-align:left;left-margin:0%;vertical-align: top;\">$rqn."."</td>";
                            $expout .= "<td style=\"width:100%\;text-align:left;\">$tmp</td>";
                            $expout .= "</tr></table>";

            
                            #$expout .= '<p class="questiontext">' . $tmp . "</p>\n";
                            $aout .= "<br>Ans:<b>($rqn) </b>";
                            $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                            foreach ($randomans1 as $answer) {
                                $answertext =$answer->answer;
                                $ansfraction = $answer->fraction;
                                $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                                if($ansfraction > 0.0) {
                                    $aout .= $answertext;
                                }
                            }
                            $tmp = $aout;
                            for($i=0;$i<count($name);$i++){
                                $x = "{".$name[$i]."}";
                                $y = $value[$i];
                                $tmp = str_replace($x,$y,$tmp);
                            }
                            $aout = $tmp;  
                            $aout .= "<br>";
                            if($choice != $or_count){
                                $expout .= "<div style=\"text-align:center\"><b>OR</b></div>";
                            }                            
                            break;
                        case 'random':
                        case 'mtf':
                        case 'ordering':                        
                            break;
                        default:
                            $expout .= "not supported";
                            break;
                    }
                    #$rqc++;
                }
                $tmp = "<div style=\"text-align:center\"><b>OR</b></div>";
                $counto = substr_count($expout,$tmp);
                $counto1 = $choice - 2;
                if($counto != $counto1){
                    $pos = strrpos($expout, "<div style=\"text-align:center\"><b>OR</b></div>");
                    if($pos !== false){
                        $expout = substr_replace($expout, "", $pos, strlen("<div style=\"text-align:center\"><b>OR</b></div>"));
                    }
                }
                
                $aout .= "<br>";

/*
                if($qtype_r == "truefalse"){
                    $tmp = $foundq[$choice];
                    $tmpm = $foundm[$choice];
                    $tmpm = sprintf('%.2f',$tmpm);
                    $ansid = $foundid[$choice];
                    $tmp = b_wrap("{$tmp}");
                    #$expout .= $tmp;
                
                    $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                    #$m = str_replace("<br>","\t",$m);                
                
                    $pos = strrpos($m, "<br>");
                    if($pos !== false){
                        $m = substr_replace($m, "", $pos, strlen("<br>"));
                    }
                    $expout .= "$m";

                    $sttrue = get_string('true', 'qtype_truefalse');
                    $stfalse = get_string('false', 'qtype_truefalse');
                    $expout .= "<ul type=\"A\" class=\"truefalse\">";
                    $expout .= "  <li id=\"quest_{$id}\">{$sttrue}</li>";
                    $expout .= "  <li id=\"quest_{$id}\">{$stfalse}</li>";
                    $expout .= "</p></ul>\n";
    
                    $aout .= "Ans:";  
                    $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                    foreach ($randomans1 as $answer) {
                        $answertext =$answer->answer;
                        $ansfraction = $answer->fraction;
                        $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                        if($ansfraction > 0.0) {
                            $aout .= $answertext;
                        }
                    }
                }
                elseif($qtype_r == "multichoice"){
                    $tmp = $foundq[$choice];
                    $tmpm = $foundm[$choice];
                    $tmpm = sprintf('%.2f',$tmpm);
                    $ansid = $foundid[$choice];
                    $tmp = b_wrap("{$tmp}");
                    #$expout .= $tmp;
                
                    $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                    #$m = str_replace("<br>","\t",$m);                
                
                    $pos = strrpos($m, "<br>");
                    if($pos !== false){
                        $m = substr_replace($m, "", $pos, strlen("<br>"));
                    }
                    $expout .= "$m";
    
                    if($question->qtype == 'multichoice' && !$question->options->single) {
                        $expout .= "<div>(Multiple Options Correct)</div>";
                        $aout .= "Ans:<table>";
                    }
                    else{
                        $aout .= "Ans:";
                    }

                    $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');


                    #$expout .= "<p class=\"ex2\">{$tmpm}</p></div>";
                    #$expout .= '<p class="questiontext">' . $tmp . "\n";

                    $expout .= "<ol type=\"A\" class=\"multichoice\">";
                
                    foreach ($randomans1 as $answer) {
                        $answertext = $answer->answer;
                        $ansfraction = $answer->fraction;
                        $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                    
                        if($question->qtype == 'multichoice' && !$question->options->single) {
                            if(1) {
                                $aout .= "<tr><td>$answertext</td><td>$ansfraction</td></tr>";
                                #$aout .= $answertext."".$ansfraction;
                                #$aout .= $ansfraction;
                            }
                        }
                        else{
                            if($ansfraction > 0.0){
                                $aout .= $answertext;
                            }
                        }
                    
                        $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                        $answer->answerformat, array('para' => false, 'newlines' => false)));
                   
                        $answertext = break_wrap($answertext);
                    
                        if ($question->options->single) {
                            $expout .= "<li id=\"quest_{$id}\">{$answertext}</li>";
                        } else {
                            $expout .= "<li id=\"quest_{$id}\">{$answertext}</li>";                        
                        }
                    }
                    if($question->qtype == 'multichoice' && !$question->options->single) {
                        $aout .= "</table>";                
                    }
                    $expout .= "</p></ol>\n";

                }
                elseif($qtype_r == "ddwtos"){
                    $tmp = $foundq[$choice];
                    $tmpm = $foundm[$choice];
                    $tmpm = sprintf('%.2f',$tmpm);
                    $ansid = $foundid[$choice];
                    $tmp = b_wrap("{$tmp}");
                    #$expout .= $tmp;
                    $tmp = preg_replace("[[[*[0-9]]]]", '', $tmp);
                    $tmp = str_replace(array('[[',']]'),'_______',$tmp);
                    $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                    #$m = str_replace("<br>","\t",$m);                
                
                    $pos = strrpos($m, "<br>");
                    if($pos !== false){
                        $m = substr_replace($m, "", $pos, strlen("<br>"));
                    }
                    $expout .= "$m";
                    $expout .= "<ol type=\"A\" class=\"ddwtos\">";
                    $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                    foreach ($randomans1 as $answer) {
                        $answertext = $answer->answer;
                        $ansfraction = $answer->fraction;                      
                        
                        $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                        $answer->answerformat, array('para' => false, 'newlines' => false)));
    
                        $answertext = break_wrap($answertext);
                        $expout .= "<li id=\"quest_{$id}\">{$answertext}</li></p>";
                    }
                    $expout .= "</ol>\n";

                    $aout .= "Ans:";
                    foreach ($randomans1 as $answer) {
                        $answertext =$answer->answer;
                        $ansfraction = $answer->fraction;
                        $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                        if($ansfraction > 0.0) {
                            $aout .= $answertext;
                        }
                    }        
                }
                elseif($qtype_r == "gapselect" OR $qtype_r == "select missing word"){
                    $tmp = $foundq[$choice];
                    $tmpm = $foundm[$choice];
                    $tmpm = sprintf('%.2f',$tmpm);
                    $ansid = $foundid[$choice];
                    $tmp = b_wrap("{$tmp}");
                    #$expout .= $tmp;
                    $tmp = preg_replace("[[[*[0-9]]]]", '', $tmp);
                    $tmp = str_replace(array('[[',']]'),'_______',$tmp);
                    $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                    #$m = str_replace("<br>","\t",$m);                
                
                    $pos = strrpos($m, "<br>");
                    if($pos !== false){
                        $m = substr_replace($m, "", $pos, strlen("<br>"));
                    }
                    $expout .= "$m";
                    $expout .= "<ol type=\"A\" class=\"ddwtos\">";
                    $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                    foreach ($randomans1 as $answer) {
                        $answertext = $answer->answer;
                        $ansfraction = $answer->fraction;                      
                        
                        $answertext= str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                        $answer->answerformat, array('para' => false, 'newlines' => false)));
    
                        $answertext = break_wrap($answertext);
                        $expout .= "<li id=\"quest_{$id}\">{$answertext}</li></p>";
                    }
                    $expout .= "</ol>\n";

                    $aout .= "Ans:";
                    foreach ($randomans1 as $answer) {
                        $answertext =$answer->answer;
                        $ansfraction = $answer->fraction;
                        $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                        if($ansfraction > 0.0) {
                            $aout .= $answertext;
                        }
                    }        
                }
                elseif($qtype_r == "essay" OR $qtype_r == "shortanswer" OR $qtype_r == "numerical" OR $qtype_r == "description"){
                    $tmp = $foundq[$choice];
                    $tmpm = $foundm[$choice];
                    $tmpm = sprintf('%.2f',$tmpm);
                    $ansid = $foundid[$choice];
                    $tmp = b_wrap("{$tmp}");
                    #$expout .= $tmp;
                    
                    $tmp = str_replace("<table>","<table class = \"qtable\" style = \"width: 90%;\">",$tmp);
                    $tmp = preg_replace("/<th[^<]*>/","<th class = \"qtable\">",$tmp);
                    $tmp = preg_replace("/<td[^<]*>/","<td class = \"qtable\">",$tmp); 
    
                    $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                    #$m = str_replace("<br>","\t",$m);                
                
                    $pos = strrpos($m, "<br>");
                    if($pos !== false){
                        $m = substr_replace($m, "", $pos, strlen("<br>"));
                    }
                    $expout .= "$m";

                    $expout .= html_writer::start_tag('ul', array('class' => 'shortanswer'));
                    $expout .= html_writer::start_tag('li');
                    #$expout .= html_writer::label(get_string('answer'), 'quest_'.$id, false, array('class' => 'accesshide'));
                    #$expout .= html_writer::empty_tag('input', array('id' => "quest_{$id}", 'name' => "quest_{$id}", 'type' => 'text'));
                    $expout .= html_writer::end_tag('li');
                    $expout .= html_writer::end_tag('ul');
    
                    $aout .= "Ans:";
                    $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                    foreach ($randomans1 as $answer) {
                        $answertext =$answer->answer;
                        $ansfraction = $answer->fraction;
                        $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                        if($ansfraction > 0.0) {
                            $aout .= $answertext;
                        }
                    }    
                }
                elseif($qtype_r == "match"){

                }
                elseif($qtype_r == "multianswer" ){

                }
                elseif($qtype_r == "calculatedsimple" OR $qtype_r == "calculatedmulti" OR $qtype_r == "calculated"){
                    $all_ans = $DB->get_records('question_datasets', array(), 'id ASC');
                    $all_ans1 = $DB->get_records('question_dataset_definitions', array(), 'id ASC');
                    $all_ans2 = $DB->get_records('question_dataset_items', array(), 'id ASC');
                    $dd_def = [];
                    $name = [];
                    $value = [];
                    $x = $foundid[$choice];
                    
                    for ($i=0; $i <= count($all_ans) ; $i++) {
                        $y =  $all_ans[$i]->question;
                        if($x == $y){
                            $dd_def[] = $all_ans[$i]-> datasetdefinition;
                        }
                    }
                    for ($i=0; $i <= count($all_ans1) ; $i++) {
                        $y =  $all_ans1[$i]-> id;
                        foreach($dd_def as $x){
                            if($x == $y){
                                $name[] = $all_ans1[$i]-> name;
                            }
                        }
                    }
                    for ($i=0; $i <= count($all_ans2) ; $i++) {
                        $y = $all_ans2[$i]-> definition;
                        foreach($dd_def as $x){
                            if($x == $y){
                                $value[] = $all_ans2[$i]-> value;
                            }
                        }
                    }
                    
                    $tmp = $foundq[$choice];
                    $tmpm = $foundm[$choice];
                    $tmpm = sprintf('%.2f',$tmpm);
                    $ansid = $foundid[$choice];
                    $tmp = b_wrap("{$tmp}");
                    #$expout .= $tmp;
                    for($i=0;$i<count($name);$i++){
                        $x = "{".$name[$i]."}";
                        $y = $value[$i];
                        $tmp = str_replace($x,$y,$tmp);
                    }    
                    $m = $tmp."<span style=\"font-size:19px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$tmpm marks]</span>";
                    #$m = str_replace("<br>","\t",$m);                
                
                    $pos = strrpos($m, "<br>");
                    if($pos !== false){
                        $m = substr_replace($m, "", $pos, strlen("<br>"));
                    }
                    $expout .= "$m";

                    $aout .= "Ans:";  
                    $randomans1 = $DB->get_records('question_answers', array('question' => $ansid), 'id ASC');  
                    foreach ($randomans1 as $answer) {
                        $answertext =$answer->answer;
                        $ansfraction = $answer->fraction;
                        $answertext = ($ansfraction > 0.0) ? b_wrap("{$answertext}") : "{$answertext}";
                        if($ansfraction > 0.0) {
                            $aout .= $answertext;
                        }
                    }

                    $tmp = $aout;
                    for($i=0;$i<count($name);$i++){
                        $x = "{".$name[$i]."}";
                        $y = $value[$i];
                        $tmp = str_replace($x,$y,$tmp);
                    }
                    $aout = $tmp;    
                }
*/
                break;
                
            default:

                // Format and add the question text.
                /*$text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $question->contextid, 'qformat_pdf');
                $tmp =  format_text($text, $question->questiontextformat, array('noclean' => true, 'para' => false, 'newlines' => false));
                #$tmp = break_wrap("{$tmp}");
                $tmp = b_wrap("{$tmp}");
                $expout .= '<p class="questiontext">' . $tmp . "</p>\n";*/

                $expout .= "<!-- export of {$question->qtype} type is not supported  -->\n";
        }
        // Close off div.
        $expout .= "</div>\n\n\n";
        $expout .= $aout;
        return $expout;
    }

    protected function presave_process($content) {
        // Override method to allow us to add xhtml headers and footers.

        global $CFG;

        // Get css bit.
        $csslines = file( "{$CFG->dirroot}/question/format/pdf/xhtml.css" );
        $css = implode( ' ', $csslines );

        //$logo = "<div class='container2'>\n<div>
        //\n</div>\n<div style='margin-left:60px;'>\n<div style=\"font-size:.6em\"><h3 class = \"heading\">College of Engineering, Pune</h3>\n</div>\n
        //<div style=\"float:right;font-size:.6em\"><h5 class = \"heading\">(An Autonomous institute of government of Maharashtra.)<br>SHIVAJI NAGAR, PUNE - 411 005</h5><hr>\n</div>\n</div>\n</div>";

        $xp =  "<!DOCTYPE html>\n";
        $xp .= "<html>\n";
        $xp .= "<head>\n";
        $xp .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
        $xp .= "<title>Moodle Quiz PDF Export</title>\n";
        $xp .= "<style type=\"text/css\">\n";
        $xp .= $css;
        $xp .= "</style>\n";
        $xp .= "</head>\n";
        $xp .= "<body>\n";
        $xp .= "<table><tr><th style =\"width:30%;margin-left:10px\"><img src=\"{$CFG->dirroot}/question/format/pdf/coep1.png\" alt=\"coep logo\"></img></th>";
        $xp .= "<th  style =\"\"><h4>COEP Technological University (COEP TECH)<br>A Unitary Public University of Government of Maharashtra.<br>SHIVAJI NAGAR, PUNE - 411 005</h4></th></tr></table><hr>";
        #$xp .= $logo;
        $xp .= $content;
        #$xp .= "<br><br><br><br><br>";
        #$content = preg_replace('/Ans:<\/li>/','',$content);
        #$xp .= $content;
        $xp .= "</body>\n";
        $xp .= "</html>\n";

        #debug_to_console("Starting");
        $mpdf = new \Mpdf\Mpdf(['defaultPageNumStyle' => '1']);
        $mpdf -> setFooter('<div style="text-align: center">{PAGENO}</div>');
        #debug_to_console("Reached");
        $mpdf->WriteHTML($xp);

        $tmp_pdf_file = tempnam($CFG->dataroot . '/mpdf', "mdl-qexp_"). ".pdf";
        chmod($tmp_pdf_file, 0644);
        $mpdf->Output($tmp_pdf_file, \Mpdf\Output\Destination::DOWNLOAD);
    
        return $tmp_pdf_file;
        // return $xp;
    }

    public function export_file_extension() {
        return '.pdf';
    }
}
