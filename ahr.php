<?php

/**
 * @author Akram Hossain <akram_cse@yahoo.com>
 * @copyright (c) 2014, Akram Hossain
 * @uses Generate Yii(1) Model From Oracle Database.If the database size is huge as like 
 * 300+ then Yii(1) unable to generate the model. for this reason i made this script 
 * to helps developer to generate Yii(1) model and saves there time.
 * @version PHP 5.6.3, ORACLE 11.2g
 */

/**
 * 
 * @param type $obj
 */

function debugPrint($obj) {
    echo '<pre>';
    print_r($obj);
    echo '</pre>';
}

// Connects to the XE service (i.e. database) on the "localhost" machine
$conn = oci_connect('BUSPERP', 'BUSPERP', '192.168.1.201/orcl');
//$conn = oci_connect('BUSPERP', 'BUSPERP', '192.168.1.1/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$genModel = $_POST['model'];

$columnSql = 'SELECT table_name, column_name, data_type, data_length,NULLABLE
FROM USER_TAB_COLUMNS
WHERE table_name = \''.$genModel.'\' ';

$stid = oci_parse($conn, $columnSql);

oci_execute($stid);

//$row = oci_fetch_assoc($stid);

$tableInfo = array();

while ($row = oci_fetch_assoc($stid)) {

    array_push($tableInfo, $row);
}

//debugPrint($tableInfo);

$property = '';

foreach ($tableInfo as $info)
{
    $property.='  * @property '.$info['DATA_TYPE'].' $'.$info['COLUMN_NAME'].' '."\n";
}





$script = "<?php"."\n"."\n";

$script.=' /**'."\n";
$script.='  * This is the model class for table "'.$genModel.'".'."\n";
$script.='  * The followings are the available columns in table \''.$genModel.'\':'."\n";
$script.=$property;
$script.='  */'."\n";

$script.= 'class '.$genModel.' extends CActiveRecord {'."\n"."\n";

$script.=' /**'."\n";
$script.='  * @return string the associated database table name.'."\n";
$script.='  */'."\n";

$script.= '  public function tableName() { '."\n";
$script.= '     return \''.$genModel.'\'; '."\n";
$script.= '  }'."\n"."\n";


$script.=' /**'."\n";
$script.='  * @return array validation rules for model attributes.'."\n";
$script.='  */'."\n";

$script.= '  public function rules() {'."\n";
$script.= '     return array('."\n";

$required = '';
$allColumn = '';
$numerical = '';
$length = '';

foreach ($tableInfo as $info)
{
    if($info['NULLABLE']=='N')
    {
        $required.=$info['COLUMN_NAME'].',';
    }
    $allColumn.=$info['COLUMN_NAME'].',';
    
    if($info['DATA_TYPE']=='NUMBER')
    {
        $numerical.=$info['COLUMN_NAME'].',';
    }
    
    $length.='        array(\''.$info['COLUMN_NAME'].'\', \'length\', \'max\' => '.$info['DATA_LENGTH'].'),'."\n";
}
$required = substr($required, 0, (strlen($required)-1));

$allColumn = substr($allColumn, 0, (strlen($allColumn)-1));

$numerical = substr($numerical, 0, (strlen($allColumn)-1));

$script.= '        array(\''.$required.'\', \'required\'),'."\n";
$script.= '        array(\''.$numerical.'\', \'numerical\'),'."\n";
$script.= $length."\n";
$script.= '        array(\''.$allColumn.'\', \'safe\', \'on\' => \'search\'),'."\n";

$script.= '     );'."\n";
$script.= '  }'."\n"."\n";

$script.=' /**'."\n";
$script.='  * @return array relational rules.'."\n";
$script.='  */'."\n";

$relationSql = 'SELECT a.table_name, a.column_name, a.constraint_name, c.owner, 
       -- referenced pk
       c.r_owner, c_pk.table_name r_table_name, c_pk.constraint_name r_pk
  FROM all_cons_columns a
  JOIN all_constraints c ON a.owner = c.owner
                        AND a.constraint_name = c.constraint_name
  JOIN all_constraints c_pk ON c.r_owner = c_pk.owner
                           AND c.r_constraint_name = c_pk.constraint_name
 WHERE c.constraint_type = \'R\'
   AND a.table_name = \''.$genModel.'\' ';

$rtid = oci_parse($conn, $relationSql);

oci_execute($rtid);

//$row = oci_fetch_assoc($stid);

$relationInfo = array();

while ($row1 = oci_fetch_assoc($rtid)) {

    array_push($relationInfo, $row1);
}

$relations = '';

foreach ($relationInfo as $rel)
{
    $relations.='         \''.str_replace("_","",$rel['R_TABLE_NAME']).'\' => array(self::BELONGS_TO,\''.$rel['R_TABLE_NAME'].'\',\''.$rel['COLUMN_NAME'].'\'),'."\n";
}


$script.= '  public function relations() {'."\n";
$script.= '     // NOTE: you may need to adjust the relation name and the related'."\n";
$script.= '     // class name for the relations automatically generated below.'."\n";
$script.= '     return array('."\n";
$script.= $relations;
$script.= '     );'."\n";
$script.= '  }'."\n"."\n";

$script.=' /**'."\n";
$script.='  * @return array customized attribute labels (name=>label)'."\n";
$script.='  */'."\n";

$script.='  public function attributeLabels() {'."\n";
$script.='   return array('."\n";

$attColumn = "";

foreach ($tableInfo as $info)
{
    $attColumn.="   '".$info['COLUMN_NAME']."' => '".str_replace('_'," ", $info['COLUMN_NAME'])."',"."\n";
}

$script.=$attColumn;

$script.='   );'."\n";
$script.='  }'."\n"."\n";

$script.=' /**'."\n";
$script.='  * Retrieves a list of models based on the current search/filter conditions.'."\n";
$script.='  * '."\n";
$script.='  * Typical usecase:'."\n";
$script.='  * - Initialize the model fields with values from filter form.'."\n";
$script.='  * - Execute this method to get CActiveDataProvider instance which will filter'."\n";
$script.='  * models according to data in model fields.'."\n";
$script.='  * - Pass data provider to CGridView, CListView or any similar widget.'."\n";
$script.='  *'."\n";
$script.='  * @return CActiveDataProvider the data provider that can return the models'."\n";
$script.='  * based on the search/filter conditions.'."\n";
$script.='  */'."\n";

$script.='  public function search() {'."\n";
$script.='     // @todo Please modify the following code to remove attributes that should not be searched.'."\n"."\n";
$script.='     $criteria = new CDbCriteria;'."\n";

$searchColumn = '';

foreach ($tableInfo as $info)
{
    $searchColumn.='     $criteria->compare(\''.$info['COLUMN_NAME'].'\', $this->'.$info['COLUMN_NAME'].');'."\n";
}

$script.= $searchColumn;

$script.='     return new CActiveDataProvider($this, array('."\n";
$script.='        \'criteria\' => $criteria,'."\n";
$script.='     ));'."\n";
$script.='  }'."\n"."\n";


$script.=' /**'."\n";
$script.='  * Returns the static model of the specified AR class.'."\n";
$script.='  * Please note that you should have this exact method in all your CActiveRecord descendants!'."\n";
$script.='  * @param string $className active record class name.'."\n";
$script.='  * @return '.$genModel.' the static model class'."\n";
$script.='  */'."\n";


$script.='  public static function model($className = __CLASS__) {'."\n";
$script.='      return parent::model($className);'."\n";
$script.='  }'."\n"."\n";

$script.='}';

$model = fopen($genModel.'.php', "w");


if(fwrite($model, $script)){
    echo '<b>'.$genModel.' model is successfully generated</b>';
}


?>
