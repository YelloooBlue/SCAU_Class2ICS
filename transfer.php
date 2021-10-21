<?php

$bodyJson = json_decode($_POST['data'], 1);
//错误码
$errorCode = $bodyJson['errorCode'];
if ($errorCode != 'success') return;
$classInfo = $bodyJson['data'];

$startTime=intval($_POST['startTime'])/1000;
//var_dump($_POST);

class calendar
{

    private $textName;
    public $eventList = array();

    //构造函数
    function __construct($calendarName)
    {
        $this->textName = $calendarName;
    }

    function makeICSText()
    {
        //日历头
        $ICSText = "BEGIN:VCALENDAR\nPRODID:-//SCAU//Schedule//" . $this->textName . "\nVERSION:2.0\n";
        $ICSText .= "DESCRIPTION:*** ICSfile converter design by YeloooBlue [www.yellowblue.top]***\n";

        foreach ($this->eventList as $aEvent) {
            $ICSText .= $aEvent->getStr();
        }

        //日历尾
        $ICSText .= "END:VCALENDAR";
        return $ICSText;
    }
}

class event
{
    public $title;//标题 文本类型
    public $startTime;
    public $endTime;
    public $description;
    public $location;
    public $repeat = false;
    public $repeatRule;

    public function getStr()
    {
        $kwargs = array(
            "SUMMARY" => $this->title,
            "DTSTART" => $this->startTime,
            "DTEND" => $this->endTime,
            "DESCRIPTION" => $this->description,
            "LOCATION" => $this->location,
        );

        if ($this->repeat) {
            $kwargs["RRULE"] = $this->repeatRule;
        }


        $str = "BEGIN:VEVENT\n";


        foreach ($kwargs as $name => $key) {


            //如果是日期类型,"DTSTART","DTEND"后由于时区设置必须有分号
            if (in_array($name, array("DTSTART", "DTEND"))) {
                $str .= $name . ";" . $key;
            } else {
                $str .= $name . ":" . $key;
            }
            $str .= "\n";
        }

        $str .= "END:VEVENT\n";
        return $str;

    }
}

$aClendar = new calendar("MyICS");


foreach ($classInfo as $x => $aClass) {


    //最终显示的课程名称
    $newClassName = $aClass['kc_name'];

    //该课程有多种方式（例如实验和理论）
    if ($aClass['arrange_num'] != 1)
        $newClassName .= "(" . $aClass['xslx_name1'] . ")";

    //课程有副标题（例如体育）
    if ($aClass['fzmc_name'])
        $newClassName .= "[" . $aClass['fzmc_name'] . "]";


    //echo "<br>WeekList=" . $aClass['pkzc'];


    $weekRangeList = explode(',', $aClass['pkzc']);

    echo "<li>";
    echo "[" . $aClass['pksj'] . "] " . $newClassName;
    echo "</li>";

    //每个课程区间（每个区间是单独一个课程）
    foreach ($weekRangeList as $no => $aRange) {

        //课程对象
        $tmp = new event;

        //课堂名称
        $tmp->title = $newClassName;
        //echo $tmp->title;

        //课堂描述
        $tmp->description = $aClass['teachernames'] . " | " . $aClass['ktmc_name'];
        //echo $tmp->description;

        //教室
        $tmp->location = $aClass['js_name'];
        //echo $tmp->location;


        //如果不是单周
        if (strpos($aRange, "-")) {
            $tmp->repeat = true;
            $aRangeArray = explode('-', $aRange);
            $startWeek = $aRangeArray[0];
            $endWeek = $aRangeArray[1];


            //单双周
            if ($aClass['sjbzcode'] == 1) {

                $tmp->repeatRule = "FREQ=WEEKLY;COUNT=" . ($endWeek - $startWeek + 1) . ";INTERVAL=1";
                //echo "<li>第" . ($no + 1) . "规则:" . $tmp->repeatRule."</li>";

            } //单周
            elseif ($aClass['sjbzcode'] == 2) {

                //echo $start.$end;

                //是否包括范围周
                if ($startWeek % 2 != 1) $startWeek++;
                if ($endWeek % 2 != 1) $endWeek--;

                $tmp->repeatRule = "FREQ=WEEKLY;COUNT=" . ((($endWeek - $startWeek) / 2) + 1) . ";INTERVAL=2";
                //echo "<li>第" . ($no + 1) . "规则:" . $tmp->repeatRule."</li>";

            } //双周
            elseif ($aClass['sjbzcode'] == 3) {

                //是否包括范围周
                if ($startWeek % 2 != 0) $startWeek++;
                if ($endWeek % 2 != 0) $endWeek--;

                $tmp->repeatRule = "FREQ=WEEKLY;COUNT=" . ((($endWeek - $startWeek) / 2) + 1) . ";INTERVAL=2";
                //echo "<li>第" . ($no + 1) . "规则:" . $tmp->repeatRule."</li>";
            }

            //第一次上课的周的 周一0：00时间戳
            $weekStartTime = $startTime + ($startWeek - 1) * 7 * 24 * 3600;
            //echo "<li>第一节课周开始时间：".$weekStartTime. "</li>";

        } else {

            //echo "<li>第" . ($no + 1) . "区间：第" . $aRange . "周</li>";

            //唯一上课的那周的 周一0：00时间戳
            $weekStartTime = $startTime + ($aRange - 1) * 7 * 24 * 3600;
            //echo "<li>第一节课周开始时间：".$weekStartTime. "</li>";
        }


        //周几上课
        $weekDay = substr($aClass['pksj'], 0, 1);
        $firstClass = $weekStartTime + ($weekDay - 1) * 24 * 3600;

        //课堂时间
        $tmp->startTime = "TZID=Asia/Shanghai:" . date('Ymd', $firstClass) . "T" . str_replace(":", "", $aClass['djkssj']) . "00";
        //echo $tmp->startTime;

        $tmp->endTime = "TZID=Asia/Shanghai:" . date('Ymd', $firstClass) . "T" . str_replace(":", "", $aClass['djjssj']) . "00";
        //echo $tmp->endTime;
        //echo "<br><div style='white-space: pre-line;'>";
        //echo $tmp->getStr();
        array_push($aClendar->eventList, $tmp);


        //fwrite($myfile, $tmp->getStr());
        // echo "</div>";
    }
    //echo "</ol>";

    //echo "<br><br>";
}
echo "<div style='font-size: small'><h3>📢请核对以上课表：</h3>";
echo"<br><br><br><br>";
echo "<div style='font-size: small'><h3>转换结果：</h3>";
echo '<textarea style="width: 90%;height: 50%;position:relative;">';
echo $aClendar->makeICSText();
echo "</textarea>";