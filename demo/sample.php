<pre><?php

//header('Content-Type: text/plain');
//error_reporting(E_ALL);

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\FileMakerValidationException;

require __DIR__ . '/../autoloader.php';

echo "==========================================" . PHP_EOL;
echo " FILEMAKER API UNIT TEST" . PHP_EOL;
echo "==========================================" . PHP_EOL . PHP_EOL;
try {

    echo "------------------------------------------" . PHP_EOL;
    echo " Test FileMaker object's main methods" . PHP_EOL;
    echo "------------------------------------------" . PHP_EOL;
    $fm = new FileMaker('filemaker-test', 'https://localhost.fmcloud.fm', 'filemaker', 'filemaker');

    $fm->useDataApi = true;

    /* API infos */
    echo "API version : " . $fm->getAPIVersion() . PHP_EOL;
    echo "Min server version : " . $fm->getMinServerVersion() . PHP_EOL . PHP_EOL;

    /* get databases list */
    echo "Get databases list...";
    $databases = $fm->listDatabases();
    echo implode(', ', $databases) . '...<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    /* get layouts list */
    echo "Get layouts list...";
    $layouts = $fm->listLayouts();
    if (sizeof($layouts) != 2) {
        echo '<span style="color:red">FAIL</span> !' . PHP_EOL;
        exit;
    }
    echo implode(', ', $layouts) . '...<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    /* get layouts list */
    echo "Get scripts list...";
    $scripts = $fm->listScripts();
    echo implode(', ', $scripts) . '...<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    /**
     * Test perform script
     */
    echo "Test perform function...";
    $command = $fm->newPerformScriptCommand($layouts[0], 'create sample data');
    $result = $command->execute();
    var_dump($result);
    echo  '...<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;
    /*
     * get layout
     */
    echo "Get a layout...";
    $layout = $fm->getLayout($layouts[0]);
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    /* get layout infos */
    echo "------------------------------------------" . PHP_EOL;
    echo " Test Layout object's main methods" . PHP_EOL;
    echo "------------------------------------------" . PHP_EOL;
    echo "Current layout : " . $layout->getName() . PHP_EOL . PHP_EOL;
    /*
     * get field List
     */
    echo "Get FieldsList... ";
    echo implode(', ', $layout->listFields()) . '...<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;
    /*
     * get a Field
     */
    echo 'Get a Field... ';
    $field = $layout->getField("text_field");
    echo $field->getName() . ' ' . $field->getResult() . '(' . $field->maxCharacters . ') ' . $field->getStyleType() . '...<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;



    echo 'Get Related sets... ' . implode(', ', $layout->listRelatedSets()) . '... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;
    echo 'Get Valuelists list... ' . implode(', ', $layout->listValueLists()) . '... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;
    echo 'Get a static value list...' . (sizeof($layout->getValueList("sample_list")) ? sizeof($layout->getValueList("sample_list")) . ' values retrived... <span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;
    echo 'Get a field value list...' . (sizeof($layout->getValueListTwoFields("ids")) ? sizeof($layout->getValueListTwoFields("ids")) . ' values retrived... <span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;


    /* get layout infos */
    echo "------------------------------------------" . PHP_EOL;
    echo " Test Field object's main methods" . PHP_EOL;
    echo "------------------------------------------" . PHP_EOL;
    echo 'Current Field : ' . $field->getName() . PHP_EOL ;
    echo 'Type : ' . $field->getType() . PHP_EOL ;
    echo 'Result : ' . $field->getResult() . PHP_EOL ;
    echo 'Repetition Count : ' . $field->getRepetitionCount() . PHP_EOL ;
    echo 'Max Length : ' . $field->getMaxCharacters() . PHP_EOL ;
    echo 'Style Type : ' . $field->getStyleType() . PHP_EOL ;
    echo 'Validation Mask : ' . $field->getValidationMask() . PHP_EOL ;
    echo 'is Auto Entered : ' . (int)$field->isAutoEntered() . PHP_EOL ;
    echo 'is Global : ' . (int)$field->isGlobal() . PHP_EOL . PHP_EOL;
    try {
        $field->validate('');
        echo 'Test EMPTY validation <span style="color:red">FAIL</span>' . PHP_EOL . PHP_EOL;
    } catch (FileMakerValidationException $ex) {
        echo 'Test EMPTY validation <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;
    }

    echo 'Test Max Length validation (' . $field->maxCharacters . ')... ';
    try {
        $field->validate(str_repeat('a', 51));
        echo '<span style="color:red">FAIL</span>' . PHP_EOL . PHP_EOL;
    } catch (FileMakerValidationException $ex) {
        echo '<span style="color:green">SUCCESS</span> ' . PHP_EOL . PHP_EOL;
    }

    echo "------------------------------------------" . PHP_EOL;
    echo " Test Find object's main methods" . PHP_EOL;
    echo "------------------------------------------" . PHP_EOL;

    echo 'Test FindAll command... ';
    $find = $fm->newFindAllCommand($layout->getName());
    $result = $find->execute();
    echo 'Found '.$result->getFetchCount().' Expected 100...'.($result->getFetchCount() == 100 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;

    /*echo 'Test FindAny command... ';
    $find = $fm->newFindAnyCommand($layout->getName());
    $result = $find->execute();
    echo 'Found '.$result->getFetchCount().' Expected 1...'.($result->getFetchCount() == 1 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;*/

    echo 'Test creating Find object from FileMaker... ';
    $find = $fm->newFindCommand($layout->getName());
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;


    echo 'Test adding preCommandScript... ';
    $find->setPreCommandScript('create sample data');;
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test adding find criterion... ';
    $find->addFindCriterion('id', 1);
    $find->addFindCriterion('text_field', '>"record #2"');
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test setting logical operator... ';
    $find->setLogicalOperator(FileMaker::FIND_OR);
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test adding preSortScript... ';
    $find->setPreSortScript('Set Order');
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test adding sort rule... ';
    $find->addSortRule('number_field', 1, FileMaker::SORT_DESCEND);
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test adding range... ';
    $find->setRange(1, 2);
    echo implode(', ', $find->getRange()).'... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test perform find... ';
    $result = $find->execute();
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Check result consistency...'. PHP_EOL;
    echo 'Record count... ';
    $count = $result->getFetchCount();
    echo 'Expected 1, returned '.$count.'... ' . ($count == 1 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;

    echo 'Record total count... ';
    $count = $result->getFoundSetCount();
    echo 'Expected 2, returned '.$count.'... ' . ($count == 2 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;

    echo 'Try to get First Record... ';
    $record = $result->getFirstRecord();
    echo ($record instanceof \airmoi\FileMaker\Object\Record ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;

    echo 'Check if expected record (ID = 2)... ';
    echo 'returned '.$record->getField('id').'... ' . ($record->getField('id') == 2 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;

    echo 'Test Create CompoundFind... ';
    $request = $fm->newCompoundFindCommand($layout->getName());
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test Create FindRequets... ';
    $findReq1 = $fm->newFindRequest($layout->getName());
    $findReq1->addFindCriterion('id', '1');
    $findReq2 = $fm->newFindRequest($layout->getName());
    $findReq2->addFindCriterion('id', '2...4');
    $findReq3 = $fm->newFindRequest($layout->getName());
    $findReq3->addFindCriterion('id', '3...4');
    $findReq3->setOmit(true);
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Add FindRequets to compoundFind... ';
    $request->add(1, $findReq1);
    $request->add(2, $findReq2);
    $request->add(3, $findReq3);
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test adding sort rule to CompoundFind... ';
    $request->addSortRule('id', 1, FileMaker::SORT_DESCEND);
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Test adding range... ';
    $request->setRange(0, 2);
    echo implode(', ', $find->getRange()).'... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Add RelatedSet Filters... ';
    $request->setRelatedSetsFilters('none', 'all');
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Get RelatedSet Filters... ';
    $filters = $request->getRelatedSetsFilters('related_sample');
    echo implode(', ', $filters).'... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Execute CompoundFind... ';
    $result = $request->execute();
    echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

    echo 'Check result consistency...'. PHP_EOL;
    echo 'Record count... ';
    $count = $result->getFetchCount();
    echo 'Expected 2, returned '.$count.'... ' . ($count == 2 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;

    echo 'Record total count... ';
    $count = $result->getFoundSetCount();
    echo 'Expected 2, returned '.$count.'... ' . ($count == 2 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;

    echo 'Try to get First Record... ';
    $record = $result->getFirstRecord();
    echo ($record instanceof \airmoi\FileMaker\Object\Record ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;

    echo 'Check if expected record (ID = 2)... ';
    echo 'returned '.$record->getField('id').'... ' . ($record->getField('id') == 2 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>') . PHP_EOL . PHP_EOL;



    echo "------------------------------------------" . PHP_EOL;
    echo " Test Record object's main methods" . PHP_EOL;
    echo "------------------------------------------" . PHP_EOL;
    echo 'Current Record "RecId" : '. $record->getRecordId(). PHP_EOL ;
    echo 'Record Layout : '. $record->getLayout()->getName(). PHP_EOL ;
    echo 'Record Fields : '. implode(', ' , $record->getFields()). PHP_EOL ;
    echo 'Record Modification Count : '. $record->getModificationId(). PHP_EOL ;
    $relatedSets = $record->getLayout()->getRelatedSets();
    echo 'Related Sets : '. implode(', ', array_keys($relatedSets)). PHP_EOL ;
    foreach ( $relatedSets as $relatedSetName => $relatedSet)
        echo 'Related Records in '.$relatedSetName.': '. sizeof($record->getRelatedSet($relatedSetName)). PHP_EOL ;

    echo PHP_EOL;

   echo 'Get record field Value... ';
   $value = $record->getField('date_field');
   echo ($value != "" ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>'). PHP_EOL . PHP_EOL;


   echo 'Get record container... ';
   $container = base64_encode($fm->getContainerData($record->getField('container_field')));
   echo "<img src='data:image/png;base64,$container' />";
   echo (sizeof($container) > 0 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>'). PHP_EOL . PHP_EOL;

   echo 'Get simple field Value List... ';
   $list = $record->getFieldValueListTwoFields('text_field');
   echo (sizeof($list) == 5 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>'). PHP_EOL . PHP_EOL;

   echo 'Get record related ValueList... ';
   $list = $record->getFieldValueListTwoFields('number_field', true);
   echo (sizeof($list) == sizeof($record->getRelatedSet($relatedSetName)) ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>' ). PHP_EOL . PHP_EOL ;

   echo 'Get a related Record... ';
   $relatedRecord = $record->getRelatedSet($relatedSetName)[0];
   echo ($relatedRecord instanceof \airmoi\FileMaker\Object\Record ? $relatedRecord->getField($relatedSetName.'::id').'... <span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>'). PHP_EOL . PHP_EOL;

   echo 'Check child parent... ';
   echo ($relatedRecord->getParent() == $record ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>'). PHP_EOL . PHP_EOL;


   echo 'Get a related Record By RecId... ';
   $relatedRecord2 = $record->getRelatedRecordById($relatedSetName, $relatedRecord->getRecordId());
   echo ($relatedRecord == $relatedRecord2 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>'). PHP_EOL . PHP_EOL;

   echo 'Testing setField... ';
   $relatedRecord->setField('text_field', 'TEST COMMIT WITH RELATED RECORDS');
   echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

   echo 'Commit changes to related record...';
   $relatedRecord->commit();
   echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

   echo "Test if modifed related record match parent's relatedRecord... ";
   echo ($record->getRelatedSet($relatedSetName)[0] == $relatedRecord ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>'). PHP_EOL . PHP_EOL;

   echo "Test creation of related record.. ";
   $currentRelatedSetCount = sizeof($record->getRelatedSet($relatedSetName));
   $newRelatedRecord = $record->newRelatedRecord($relatedSetName);
   $time = time();
   $newRelatedRecord->setField('id_sample', $record->getField('id'));
   $newRelatedRecord->setField('text_field', "NEW RELATED RECORD");
   $newRelatedRecord->setField('number_field', rand(1,1000));
   $newRelatedRecord->setField('date_field', date('m/d/Y', $time));
   $newRelatedRecord->setField('time_field', date('H:i:s', $time));
   $newRelatedRecord->setField('timestamp_field', date('m/d/Y H:i:s', $time));
   $newRelatedRecord->commit();
   echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

   echo "Check if parent's relatedSet has been updated... ";
   echo (sizeof($record->getRelatedSet($relatedSetName)) == $currentRelatedSetCount+1 ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAIL</span>' ) . PHP_EOL . PHP_EOL;

   echo "test duplicate record... ";
   $duplicateCommand = $fm->newDuplicateCommand($layout->getName(), $record->getRecordId());
   $result = $duplicateCommand->execute();
   echo 'New record count '.$result->getTableRecordCount().'... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

   echo "test create record... ";
   $newRecord = $fm->newAddCommand($layout->getName());
   $time = time();
   $newRecord->setField('id_sample', $record->getField('id'));
   $newRecord->setField('text_field', "NEW RELATED RECORD");
   $newRecord->setField('number_field', rand(1,1000));
   $newRecord->setField('date_field', date('m/d/Y', $time));
   $newRecord->setField('time_field', date('H:i:s', $time));
   $newRecord->setField('timestamp_field', date('m/d/Y H:i:s', $time));
   $result = $newRecord->execute();
   $recordId = $result->getFirstRecord()->getRecordId();
   echo 'New record count '.$result->getTableRecordCount().'... ';
   echo '<span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;

   echo "test delete record... ";
   $delCommand = $fm->newDeleteCommand($layout->getName(), $recordId);
   $result = $delCommand->execute();
   echo 'New record count '.$result->getTableRecordCount().'... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;


   echo "test Record validation rules... ";
   $updateCommand = $fm->newEditCommand($layout->getName(), $record->getRecordId(), ['text_field'=> str_repeat('a', 51)]);
   try {
        $updateCommand->validate();
   } catch (\airmoi\FileMaker\FileMakerValidationException $e ) {
       if ( $e->getErrors('text_field')[0][1] == FileMaker::RULE_MAXCHARACTERS)
        echo '... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;
       else
        echo '... <span style="color:red">FAIL</span>' . PHP_EOL . PHP_EOL;
   }

   echo "test Add record... ";
   $addCommand = $fm->newAddCommand($layout->getName(), ['text_field' => 'Test Add Command']);
   $addCommand->setField('number_field', rand(1,2000));
   $addCommand->setFieldFromTimestamp('date_field', time());
   $addCommand->setFieldFromTimestamp('timestamp_field', time());
   $addCommand->setFieldFromTimestamp('time_field', time());
   $result = $addCommand->execute();
   echo 'New record count '.$result->getTableRecordCount().'... <span style="color:green">SUCCESS</span>' . PHP_EOL . PHP_EOL;



} catch (FileMakerException $e) {
    echo PHP_EOL;
    echo "EXCEPTION :" . PHP_EOL;
    echo "  - At :" . $e->getFile() . ' line ' . $e->getLine() . PHP_EOL;
    echo "  - Code :" . $e->getCode() . PHP_EOL;
    echo "  - Message :" . $e->getMessage() . PHP_EOL;
    echo "  - Stack :" . $e->getTraceAsString() . PHP_EOL;
} catch (Exception $e) {
    echo PHP_EOL;
    echo "EXCEPTION :" . PHP_EOL;
    echo "  - At :" . $e->getFile() . ' line ' . $e->getLine() . PHP_EOL;
    echo "  - Code :" . $e->getCode() . PHP_EOL;
    echo "  - Message :" . $e->getMessage() . PHP_EOL;
    echo "  - Stack :" . $e->getTraceAsString() . PHP_EOL;
}
