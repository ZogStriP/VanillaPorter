<?php
/**
 * @copyright Vanilla Forums Inc. 2010
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL2
 * @package VanillaPorter
 */

/**
 * Object for exporting other database structures into a format that can be imported.
 */
class ExportModel {
   const COMMENT = '//';
   const DELIM = ',';
   const ESCAPE = '\\';
   const NEWLINE = "\n";
   const NULL = '\N';
   const QUOTE = '"';

   public $CaptureOnly = FALSE;

   /** @var array Any comments that have been written during the export. */
   public $Comments = array();

   /** @var ExportController **/
   public $Controller = NULL;

   /** @var string The charcter set to set as the connection anytime the database connects. */
   public $CharacterSet = 'utf8';
   
   /**
    * @var int The chunk size when exporting large tables.
    */
   public $ChunkSize = 100000;

   /** @var array **/
   public $CurrentRow = NULL;

   public $Destination = 'file';
   
   public $DestPrefix = 'GDN_z';

   /** @var object File pointer */
   protected $_File = NULL;

   /** @var string A prefix to put into an automatically generated filename. */
   public $FilenamePrefix = '';

   public $_Host;

   /** @var object PDO instance */
   protected $_PDO = NULL;

   protected $_Password;

   /** @var string The path to the export file. */
   public $Path = '';

   /**
    * @var string The database prefix. When you pass a sql string to ExportTable() it will replace occurances of :_ with this property.
    * @see ExportModel::ExportTable()
    */
   public $Prefix = '';

   public $Queries = array();
   
   protected $_QueryStructures = array();

   /** @var string The path to the source of the export in the case where a file is being converted. */
   public $SourcePath = '';
   
   /**
    * @var string 
    */
   public $SourcePrefix = '';
   
   public $ScriptCreateTable = TRUE;

   /**
    * @var array Strucutes that define the format of the export tables.
    */
   protected $_Structures = array(
      'Activity' => array(
            'ActivityType' => 'varchar(20)',
            'ActivityUserID' => 'int',
            'RegardingUserID' => 'int',
            'NotifyUserID' => 'int',
            'HeadlineFormat' => 'varchar(255)',
            'Story' => 'text',
            'InsertUserID' => 'int',
            'DateInserted' => 'datetime',
            'InsertIPAddress' => 'varchar(15)'),
      'Category' => array(
            'CategoryID' => 'int',
            'Name' => 'varchar(30)',
            'UrlCode' => 'varchar(255)',
            'Description' => 'varchar(250)',
            'ParentCategoryID' => 'int',
            'DateInserted' => 'datetime',
            'InsertUserID' => 'int',
            'DateUpdated' => 'datetime',
            'UpdateUserID' => 'int',
            'Sort' => 'int',
            'Archived' => 'tinyint(1)'),
      'Comment' => array(
            'CommentID' => 'int',
            'DiscussionID' => 'int',
            'DateInserted' => 'datetime',
            'InsertUserID' => 'int',
            'InsertIPAddress' => 'varchar(15)',
            'DateUpdated' => 'datetime',
            'UpdateUserID' => 'int',
            'UpdateIPAddress' => 'varchar(15)',
            'Format' => 'varchar(20)',
            'Body' => 'text',
            'Score' => 'float'),
      'Conversation' => array(
            'ConversationID' => 'int',
            'Subject' => 'varchar(255)',
            'FirstMessageID' => 'int',
            'DateInserted' => 'datetime',
            'InsertUserID' => 'int',
            'DateUpdated' => 'datetime',
            'UpdateUserID' => 'int'),
      'ConversationMessage' => array(
            'MessageID' => 'int',
            'ConversationID' => 'int',
            'Body' => 'text',
            'Format' => 'varchar(20)',
            'InsertUserID' => 'int',
            'DateInserted' => 'datetime',
            'InsertIPAddress' => 'varchar(15)'),
      'Discussion' => array(
            'DiscussionID' => 'int',
            'Name' => 'varchar(100)',
            'Body' => 'text',
            'Format' => 'varchar(20)',
            'CategoryID' => 'int',
            'DateInserted' => 'datetime',
            'InsertUserID' => 'int',
            'InsertIPAddress' => 'varchar(15)',
            'DateUpdated' => 'datetime',
            'UpdateUserID' => 'int',
            'UpdateIPAddress' => 'varchar(15)',
            'DateLastComment' => 'datetime',
            'CountComments' => 'int',
            'CountViews' => 'int',
            'Score' => 'float',
            'Closed' => 'tinyint',
            'Announce' => 'tinyint',
            'Sink' => 'tinyint'),
      'Media' => array(
            'MediaID' => 'int',
            'Name' => 'varchar(255)',
            'Type' => 'varchar(128)',
            'Size' => 'int',
            'StorageMethod' => 'varchar(24)',
            'Path' => 'varchar(255)',
            'InsertUserID' => 'int',
            'DateInserted' => 'datetime',
            'ForeignID' => 'int',
            'ForeignTable' => 'varchar(24)',
            'ImageWidth' => 'int',
            'ImageHeight' => 'int'
          ),
      'Permission' => array(
            'RoleID' => 'int',
            '_Permissions' => 'varchar(255)',
            'Garden.SignIn.Allow' => 'tinyint',
            'Garden.Activity.View' => 'tinyint',
            'Garden.Profiles.View' => 'tinyint',
            'Vanilla.Discussions.View' => 'tinyint',
            'Vanilla.Discussions.Add' => 'tinyint',
            'Vanilla.Comments.Add' => 'tinyint'
          ),
      'Role' => array(
            'RoleID' => 'int',
            'Name' => 'varchar(100)',
            'Description' => 'varchar(200)',
            'CanSession' => 'tinyint'),
      'Tag' => array(
            'TagID' => 'int',
            'Name' => 'varchar(255)',
            'InsertUserID' => 'int',
            'DateInserted' => 'datetime'),
      'TagDiscussion' => array(
            'TagID' => 'int',
            'DiscussionID' => 'int'),
      'User' => array(
            'UserID' => 'int',
            'Name' => 'varchar(20)',
            'Email' => 'varchar(200)',
            'Password' => 'varbinary(100)',
            'HashMethod' => 'varchar(10)',
            //'Gender' => array('m', 'f'),
            'Title' => 'varchar(100)',
            'Location' => 'varchar(100)',
            'Score' => 'float',
            'InviteUserID' => 'int',
            'HourOffset' => 'int',
            'CountDiscussions' => 'int',
            'CountComments' => 'int',
            'DiscoveryText' => 'text',
            'Photo' => 'varchar(255)',
            'DateOfBirth' => 'datetime',
            'DateFirstVisit' => 'datetime',
            'DateLastActive' => 'datetime',
            'DateInserted' => 'datetime',
            'InsertIPAddress' => 'varchar(15)',
            'LastIPAddress' => 'varchar(15)',
            'DateUpdated' => 'datetime',
            'Banned' => 'tinyint',
            'ShowEmail' => 'tinyint'),
      'UserAuthentication' => array(
          'ForeignUserKey' => 'varchar(255)',
          'ProviderKey' => 'varchar(64)',
          'UserID' => 'varchar(11)',
          'Attributes' => 'text'
          ),
      'UserConversation' => array(
            'UserID' => 'int',
            'ConversationID' => 'int',
            'Deleted' => 'tinyint(1)',
            'LastMessageID' => 'int'),
      'UserDiscussion' => array(
            'UserID' => 'int',
            'DiscussionID' => 'int',
            'Bookmarked' => 'tinyint',
            'DateLastViewed' => 'datetime',
            'CountComments' => 'int'),
      'UserMeta' => array(
            'UserID' => 'int',
            'Name' => 'varchar(255)',
            'Value' => 'text'),
      'UserRole' => array(
            'UserID' => 'int',
            'RoleID' => 'int')
   );

   public $TestMode = FALSE;
   
   public $TestLimit = 10;

   /**
    * @var bool Whether or not to use compression when creating the file.
    */
   protected $_UseCompression = TRUE;

   protected $_Username;

   /**
    *
    * @var bool Whether or not to stream the export the the output rather than save a file.
    */
   public $UseStreaming = FALSE;


   /**
    * Create the export file and begin the export.
    * @param string $Path The path to the export file.
    * @param string $Source The source program that created the export. This may be used by the import routine to do additional processing.
    */
   public function BeginExport($Path = '', $Source = '', $Header = array()) {
      $this->Comments = array();
      $this->BeginTime = microtime(TRUE);

      if($Path)
         $this->Path = $Path;
      if(!$this->Path)
         $this->Path = 'export_'.($this->FilenamePrefix ? $this->FilenamePrefix.'_' : '').date('Y-m-d_His').'.txt'.($this->UseCompression() ? '.gz' : '');

      $fp = $this->_OpenFile();

      $Comment = 'Vanilla Export: '.$this->Version();
      
      if($Source)
         $Comment .= self::DELIM.' Source: '.$Source;
      foreach ($Header as $Key => $Value) {
         $Comment .= self::DELIM." $Key: $Value";
      }
      
      if ($this->CaptureOnly)
         $this->Comment($Comment);
      else
         fwrite($fp, $Comment.self::NEWLINE.self::NEWLINE);
     
      $this->Comment('Export Started: '.date('Y-m-d H:i:s'));

      return $fp;
   }

   /**
    * Write a comment to the export file.
    * @param string $Message The message to write.
    * @param bool $Echo Whether or not to echo the message in addition to writing it to the file.
    */
   public function Comment($Message, $Echo = TRUE) {
      if ($this->Destination == 'file')
         $Char = self::COMMENT;
      else
         $Char = '--';
         
      $Comment = $Char.' '.str_replace(self::NEWLINE, self::NEWLINE.self::COMMENT.' ', $Message).self::NEWLINE;
      
      fwrite($this->_File, $Comment);
      if($Echo)
         $this->Comments[] = $Message;
   }

   /**
    * End the export and close the export file. This method must be called if BeginExport() has been called or else the export file will not be closed.
    */
   public function EndExport() {
      $this->EndTime = microtime(TRUE);
      $this->TotalTime = $this->EndTime - $this->BeginTime;

      $this->Comment('Export Completed: '.date('Y-m-d H:i:s'));
      $this->Comment(sprintf('Elapsed Time: %s', self::FormatElapsed($this->TotalTime)));

      if ($this->TestMode || $this->Controller->Param('dumpsql') || $this->CaptureOnly) {
         $Queries = implode("\n\n", $this->Queries);
         if ($this->Destination == 'database')
            fwrite($this->_File, $Queries);
         else
            $this->Comment($Queries, TRUE);
      }
      
      if($this->UseStreaming) {
         //ob_flush();
      } else {
         if($this->UseCompression() && function_exists('gzopen'))
            gzclose($this->_File);
         else
            fclose($this->_File);
      }

      
   }

   /**
    * Export a table to the export file.
    * @param string $TableName the name of the table to export. This must correspond to one of the accepted Vanilla tables.
    * @param mixed $Query The query that will fetch the data for the export this can be one of the following:
    *  - <b>String</b>: Represents a string of SQL to execute.
    *  - <b>PDOStatement</b>: Represents an already executed query result set.
    *  - <b>Array</b>: Represents an array of associative arrays or objects containing the data in the export.
    *  @param array $Mappings Specifies mappings, if any, between the source and the export where the keys represent the source columns and the values represent Vanilla columns.
    *	  - If you specify a Vanilla column then it must be in the export structure contained in this class.
    *   - If you specify a MySQL type then the column will be added.
    *   - If you specify an array you can have the following keys: Column, and Type where Column represents the new column name and Type represents the MySQL type.
    *  For a list of the export tables and columns see $this->Structure().
    */
   public function ExportTable($TableName, $Query, $Mappings = array()) {
      $BeginTime = microtime(TRUE);

      $RowCount = $this->_ExportTable($TableName, $Query, $Mappings);

      $EndTime = microtime(TRUE);
      $Elapsed = self::FormatElapsed($BeginTime, $EndTime);
      $this->Comment("Exported Table: $TableName ($RowCount rows, $Elapsed)");
      fwrite($this->_File, self::NEWLINE);
   }
   
   protected function _ExportTableImport($TableName, $Query, $Mappings = array()) {
      // Backup the settings.
      $DestinationBak = $this->Destination;
      $this->Destination = 'file';
      
      $_FileBak = $this->_File;
      $Path = dirname(__FILE__).'/'.$TableName.'.csv';
      $this->Comment("Exporting To: $Path");
      $fp = fopen($Path, 'wb');
      $this->_File = $fp;
      
      // First export the file to a file.
      $this->_ExportTable($TableName, $Query, $Mappings, array('NoEndline' => TRUE));
      
      // Now define a table to import into.
      $this->_CreateExportTable($TableName, $Query, $Mappings);
      
      // Now load the data.
      $Sql = "load data local infile '$Path' into table {$this->DestDb}.{$this->DestPrefix}$TableName
         character set utf8
         columns terminated by ','
         optionally enclosed by '\"'
         escaped by '\\\\'
         lines terminated by '\\n'
         ignore 2 lines";
      $this->Query($Sql);
      
      // Restore the settings.
      $this->Destination = $DestinationBak;
      $this->_File = $_FileBak;
   }
   
   public function ExportBlobs($Sql, $BlobColumn, $PathColumn, $Thumbnail = FALSE) {
      $Result = $this->Query($Sql);
      if (!$Result) {
         die("Sql error: $Sql");
      }
      
      while ($Row = mysql_fetch_assoc($Result)) {
         $Path = dirname(__FILE__).'/'.$Row[$PathColumn];
         if (!file_exists(dirname($Path))) {
            $R = mkdir(dirname($Path), 0777, TRUE); 
            if (!$R)
               die("Could not create ".dirname($Path));
         }
         
         $PicPath = str_replace('/avat', '/pavat', $Path);
         $fp = fopen($PicPath, 'wb');
         fwrite($fp, $Row[$BlobColumn]);
         fclose($fp);
         
         if ($Thumbnail) {
            if ($Thumbnail === TRUE)
               $Thumbnail = 50;
            
            $ThumbPath = str_replace('/avat', '/navat', $Path);
            $this->GenerateThumbnail($PicPath, $ThumbPath, $Thumbnail, $Thumbnail);
         }
      }
   }

   protected function _ExportTable($TableName, $Query, $Mappings = array(), $Options = array()) {
      $fp = $this->_File;

      // Make sure the table is valid for export.
      if (!array_key_exists($TableName, $this->_Structures)) {
         $this->Comment("Error: $TableName is not a valid export."
            ." The valid tables for export are ". implode(", ", array_keys($this->_Structures)));
         fwrite($fp, self::NEWLINE);
         return;
      }

      if ($this->Destination == 'database') {
         $this->_ExportTableDB($TableName, $Query, $Mappings);
         return;
      }
      
      // Check for a chunked query.
      $Query = str_replace('{from}', -2000000000, $Query);
      $Query = str_replace('{to}', 2000000000, $Query);
      
      if (strpos($Query, '{from}') !== FALSE) {
         $this->_ExportTableDBChunked($TableName, $Query, $Mappings);
         return;
      }

      // If we are in test mode then limit the query.
      if ($this->TestMode && $this->TestLimit) {
         $Query = rtrim($Query, ';');
         if (stripos($Query, 'select') !== FALSE && stripos($Query, 'limit') === FALSE) {
            $Query .= " limit {$this->TestLimit}";
         }
      }

      $Structure = $this->_Structures[$TableName];

      // Set the search and replace to escape strings.
      $EscapeSearch = array(self::ESCAPE, self::DELIM, self::NEWLINE, self::QUOTE); // escape must go first
      $EscapeReplace = array(self::ESCAPE.self::ESCAPE, self::ESCAPE.self::DELIM, self::ESCAPE.self::NEWLINE, self::ESCAPE.self::QUOTE);

      $LastID = 0;
      $IDName = 'NOTSET';
      $FirstQuery = TRUE;

      // Get the filters from the mappings.
      $Filters = array();
      foreach ($Mappings as $Column => $Mapping) {
         if (is_array($Mapping) &&isset($Mapping['Column']) && isset($Mapping['Filter'])) {
            $Filters[$Mapping['Column']] = $Mapping['Filter'];
         }
      }

      $Data = $this->Query($Query);
      $Mb = function_exists('mb_detect_encoding');

      // Loop through the data and write it to the file.
      $RowCount = 0;
      if ($Data !== FALSE) {
         while (($Row = mysql_fetch_assoc($Data)) !== FALSE) {
            $Row = (array)$Row; // export%202010-05-06%20210937.txt
            $this->CurrentRow =& $Row;
            $RowCount++;
            
            if($FirstQuery) {
               // Start with the table name.
               fwrite($fp, 'Table: '.$TableName.self::NEWLINE);

               // Get the export structure.
               $ExportStructure = $this->GetExportStructure($Row, $Structure, $Mappings);

               // Build and write the table header.
               $TableHeader = $this->_GetTableHeader($ExportStructure, $Structure);

               fwrite($fp, $TableHeader.self::NEWLINE);

               $Mappings = array_flip($Mappings);

               $FirstQuery = FALSE;
            }

            $First = TRUE;

            // Loop through the columns in the export structure and grab their values from the row.
            $ExRow = array();
            foreach ($ExportStructure as $Field => $Type) {
               // Get the value of the export.
               if (array_key_exists($Field, $Row)) {
                  // The column has an exact match in the export.
                  $Value = $Row[$Field];
               } elseif (array_key_exists($Field, $Mappings) && isset($Row[$Mappings[$Field]])) {
                  // The column is mapped.
                  $Value = $Row[$Mappings[$Field]];
               } else {
                  $Value = NULL;
               }

               // Check to see if there is a callback filter.
               if (isset($Filters[$Field])) {
                  $Callback = $Filters[$Field];
                  $Row2 =& $Row;
                  $Value = call_user_func($Filters[$Field], $Value, $Field, $Row2, $Column);
                  $Row = $this->CurrentRow;
               }

               // Format the value for writing.
               if (is_null($Value)) {
                  $Value = self::NULL;
               } elseif (is_numeric($Value)) {
                  // Do nothing, formats as is.
               } elseif (is_string($Value)) {

                  // Check to see if there is a callback filter.
                  if (isset($Filters[$Field])) {
                     //$Value = call_user_func($Filters[$Field], $Value, $Field, $Row);
                  } else {
                     if($Mb && mb_detect_encoding($Value) != 'UTF-8')
                        $Value = utf8_encode($Value);
                  }

                  $Value = str_replace(array("\r\n", "\r"), array(self::NEWLINE, self::NEWLINE), $Value);
                  $Value = self::QUOTE
                     .str_replace($EscapeSearch, $EscapeReplace, $Value)
                     .self::QUOTE;
               } elseif (is_bool($Value)) {
                  $Value = $Value ? 1 : 0;
               } else {
                  // Unknown format.
                  $Value = self::NULL;
               }

               $ExRow[] = $Value;
            }
            // Write the data.
            fwrite($fp, implode(self::DELIM, $ExRow));
            // End the record.
            fwrite($fp, self::NEWLINE);
         }
      }
      if($Data !== FALSE)
         mysql_free_result($Data);
      unset($Data);

      if (!isset($Options['NoEndline'])) {
         // Write an empty line to signify the end of the table.
         fwrite($fp, self::NEWLINE);
      }
      
      mysql_close();

      return $RowCount;
   }
   
   protected function _CreateExportTable($TableName, $Query, $Mappings = array()) {
      if (!$this->ScriptCreateTable)
         return;
      
      // Limit the query to grab any additional columns.
      $QueryStruct = rtrim($Query, ';').' limit 1';
      $Structure = $this->_Structures[$TableName];
      
      $Data = $this->Query($QueryStruct, TRUE);
//      $Mb = function_exists('mb_detect_encoding');

      // Loop through the data and write it to the file.
      if ($Data === FALSE)
         return;
      
      // Get the export structure.
      while (($Row = mysql_fetch_assoc($Data)) !== FALSE) {
         $Row = (array)$Row;

         // Get the export structure.
         $ExportStructure = $this->GetExportStructure($Row, $Structure, $Mappings);

         $Mappings = array_flip($Mappings);
         break;
      }
      mysql_close($Data);
      
      // Build the create table statement.
      $ColumnDefs = array();
      foreach ($ExportStructure as $ColumnName => $Type) {
         $ColumnDefs[] = "`$ColumnName` $Type";
      }
      $DestDb = '';
      if (isset($this->DestDb))
         $DestDb = $this->DestDb.'.';
      
      $this->Query("drop table if exists {$DestDb}{$this->DestPrefix}$TableName");
      $CreateSql = "create table {$DestDb}{$this->DestPrefix}$TableName (\n  ".implode(",\n  ", $ColumnDefs)."\n) engine=innodb";
      
      $this->Query($CreateSql);
   }

   protected function _ExportTableDB($TableName, $Query, $Mappings = array()) {
      if ($this->HasFilter($Mappings) || strpos($Query, 'union all') !== FALSE) {
         $this->_ExportTableImport($TableName, $Query, $Mappings);
         return;
      }
      
      // Check for a chunked query.
      if (strpos($Query, '{from}') !== FALSE) {
         $this->_ExportTableDBChunked($TableName, $Query, $Mappings);
         return;
      }
      
      $DestDb = '';
      if (isset($this->DestDb))
         $DestDb = $this->DestDb.'.';

      // Limit the query to grab any additional columns.
      $QueryStruct = $this->GetQueryStructure($Query, $TableName);
      $Structure = $this->_Structures[$TableName];
      
      $ExportStructure = $this->GetExportStructure($QueryStruct, $Structure, $Mappings);

      $Mappings = array_flip($Mappings);

      // Build the create table statement.
      $ColumnDefs = array();
      foreach ($ExportStructure as $ColumnName => $Type) {
         $ColumnDefs[] = "`$ColumnName` $Type";
      }
      if ($this->ScriptCreateTable) {
         $this->Query("drop table if exists {$DestDb}{$this->DestPrefix}$TableName");
         $CreateSql = "create table {$DestDb}{$this->DestPrefix}$TableName (\n  ".implode(",\n  ", $ColumnDefs)."\n) engine=innodb";
         $this->Query($CreateSql);
      }

      $Query = rtrim($Query, ';');
      // Build the insert statement.
      if ($this->TestMode && $this->TestLimit) {
         $Query .= " limit {$this->TestLimit}";
      }
      
//      echo $Query."\n\n\n";
//      die();
//      print_r(ParseSelect($Query));

      $InsertColumns = array();
      $SelectColumns = array();
      foreach ($ExportStructure as $ColumnName => $Type) {         
         $InsertColumns[] = '`'.$ColumnName.'`';
         if (isset($Mappings[$ColumnName])) {
            $SelectColumns[$ColumnName] = $Mappings[$ColumnName];
         } else {
            $SelectColumns[$ColumnName] = $ColumnName;
         }
      }
//      print_r($SelectColumns);
      
      $Query = ReplaceSelect($Query, $SelectColumns);

      $InsertSql = "replace {$DestDb}{$this->DestPrefix}$TableName"
         ." (\n  ".implode(",\n   ", $InsertColumns)."\n)\n"
         .$Query;
      
//      die($InsertSql);
      $this->Query($InsertSql);
   }
   
   protected function _ExportTableDBChunked($TableName, $Query, $Mappings = array()) {
      // Grab the table name from the first from.
      if (preg_match('`\sfrom\s([^\s]+)`', $Query, $Matches)) {
         $From = $Matches[1];
      } else {
         trigger_error("Could not figure out table for $TableName chunking.", E_USER_WARNING);
         return;
      }
      
      $Sql = "show table status like '{$From}';";
      $R = $this->Query($Sql, TRUE);
      $Row = mysql_fetch_assoc($R);
      mysql_free_result($R);
      $Max = $Row['Auto_increment'];
      
      if (!$Max)
         $Max = 2000000;
      
      for ($i = 0; $i < $Max; $i += $this->ChunkSize) {
         $From = $i;
         $To = $From + $this->ChunkSize - 1;
         
         $Sql = str_replace(array('{from}', '{to}'), array($From, $To), $Query);
         $this->_ExportTableDB($TableName, $Sql, $Mappings);
      }
   }
   
   public function FixPermissionColumns($Columns) {
      $Result = array();
      foreach ($Columns as $Index => $Value) {
         if (is_string($Value) && strpos($Value, '.') !== FALSE)
            $Value = array('Column' => $Value, 'Type' => 'tinyint(1)');
         $Result[$Index] = $Value;
      }
      return $Result;
   }
   
   public function ForceDate($Value) {
      if (!$Value || preg_match('`0000-00-00`', $Value)) {
         return gmdate('Y-m-d H:i:s');
      }
      return $Value;
   }

   static function FormatElapsed($Start, $End = NULL) {
      if($End === NULL)
         $Elapsed = $Start;
      else
         $Elapsed = $End - $Start;

      $m = floor($Elapsed / 60);
      $s = $Elapsed - $m * 60;
      $Result = sprintf('%02d:%05.2f', $m, $s);

      return $Result;
   }

   static function FormatValue($Value) {
      static $EscapeSearch = NULL; if ($EscapeSearch === NULL) $EscapeSearch = array(self::ESCAPE, self::DELIM, self::NEWLINE, self::QUOTE); // escape must go first
      static $EscapeReplace = NULL; if ($EscapeReplace === NULL) $EscapeReplace = array(self::ESCAPE.self::ESCAPE, self::ESCAPE.self::DELIM, self::ESCAPE.self::NEWLINE, self::ESCAPE.self::QUOTE);

      // Format the value for writing.
      if (is_null($Value)) {
         $Value = self::NULL;
      } elseif (is_numeric($Value)) {
         // Do nothing, formats as is.
      } elseif (is_string($Value)) {
         if($Mb && mb_detect_encoding($Value) != 'UTF-8')
            $Value = utf8_encode($Value);

         $Value = str_replace(array("\r\n", "\r"), array(self::NEWLINE, self::NEWLINE), $Value);
         $Value = self::QUOTE
            .str_replace($EscapeSearch, $EscapeReplace, $Value)
            .self::QUOTE;
      } elseif (is_bool($Value)) {
         $Value = $Value ? 1 : 0;
      } else {
         // Unknown format.
         $Value = self::NULL;
      }
      return $Value;
   }
   
   public function GenerateThumbnail($Path, $ThumbPath, $Height = 50, $Width = 50) {
      list($WidthSource, $HeightSource, $Type) = getimagesize($Path);
      
      $XCoord = 0;
      $YCoord = 0;
      $HeightDiff = $HeightSource - $Height;
      $WidthDiff = $WidthSource - $Width;
      if ($WidthDiff > $HeightDiff) {
         // Crop the original width down
         $NewWidthSource = round(($Width * $HeightSource) / $Height);

         // And set the original x position to the cropped start point.
         $XCoord = round(($WidthSource - $NewWidthSource) / 2);
         $WidthSource = $NewWidthSource;
      } else {
         // Crop the original height down
         $NewHeightSource = round(($Height * $WidthSource) / $Width);

         // And set the original y position to the cropped start point.
         $YCoord = round(($HeightSource - $NewHeightSource) / 2);
         $HeightSource = $NewHeightSource;
      }
      
      switch ($Type) {
            case 1:
               $SourceImage = imagecreatefromgif($Path);
            break;
         case 2:
               $SourceImage = imagecreatefromjpeg($Path);
            break;
         case 3:
            $SourceImage = imagecreatefrompng($Path);
            imagealphablending($SourceImage, TRUE);
            break;
      }
      
      $TargetImage = imagecreatetruecolor($Width, $Height);
      imagecopyresampled($TargetImage, $SourceImage, 0, 0, $XCoord, $YCoord, $Width, $Height, $WidthSource, $HeightSource);
      imagedestroy($SourceImage);
      
      switch ($Type) {
         case 1:
            imagegif($TargetImage, $ThumbPath);
            break;
         case 2:
            imagejpeg($TargetImage, $ThumbPath);
            break;
         case 3:
            imagepng($TargetImage, $ThumbPath);
            break;
      }
      imagedestroy($TargetImage);
//      die('</pre>foo');
   }

   public function GetCharacterSet($Table) {
      // First get the collation for the database.
      $Data = $this->Query("show table status like ':_{$Table}';");
      if (!$Data)
         return FALSE;
      if ($StatusRow = mysql_fetch_assoc($Data))
         $Collation = $StatusRow['Collation'];
      else
         return FALSE;

      // Grab the character set from the database.
      $Data = $this->Query("show collation like '$Collation'");
      if (!$Data)
         return $False;
      if ($CollationRow = mysql_fetch_assoc($Data)) {
         $CharacterSet = $CollationRow['Charset'];
         return $CharacterSet;
      }
      return FALSE;
   }

   public function GetDatabasePrefixes() {
      // Grab all of the tables.
      $Data = $this->Query('show tables');
      if ($Data === FALSE)
         return array();

      // Get the names in an array for easier parsing.
      $Tables = array();
      while (($Row = mysql_fetch_array($Data, MYSQL_NUM)) !== FALSE) {
         $Tables[] = $Row[0];
      }
      sort($Tables);

      $Prefixes = array();

      // Loop through each table and get it's prefixes.
      foreach ($Tables as $Table) {
         $PxFound = FALSE;
         foreach ($Prefixes as $PxIndex => $Px) {
            $NewPx = $this->_GetPrefix($Table, $Px);
            if (strlen($NewPx) > 0) {
               $PxFound = TRUE;
               if ($NewPx != $Px) {
                  $Prefixes[$PxIndex] = $NewPx;
               }
               break;
            }
         }
         if (!$PxFound) {
            $Prefixes[] = $Table;
         }
      }
      return $Prefixes;
   }

   protected function _GetPrefix($A, $B) {
      $Length = min(strlen($A), strlen($B));
      $Prefix = '';

      for ($i = 0; $i < $Length; $i++) {
         if ($A[$i] == $B[$i])
            $Prefix .= $A[$i];
         else
            break;
      }
      return $Prefix;
   }

   public function GetExportStructure($Row, $Structure, &$Mappings) {
      $ExportStructure = array();
      // See what columns from the structure are in

      // See what columns to add to the end of the structure.
      foreach($Row as $Column => $X) {
         if(array_key_exists($Column, $Mappings)) {
            $Mapping = $Mappings[$Column];
            if(is_string($Mapping)) {
               if(array_key_exists($Mapping, $Structure)) {
                  // This an existing column.
                  $DestColumn = $Mapping;
                  $DestType = $Structure[$DestColumn];
               } else {
                  // This is a created column.
                  $DestColumn = $Column;
                  $DestType = $Mapping;
               }
            } elseif(is_array($Mapping)) {
               $DestColumn = $Mapping['Column'];
               if (isset($Mapping['Type']))
                  $DestType = $Mapping['Type'];
               elseif(isset($Structure[$DestColumn]))
                  $DestType = $Structure[$DestColumn];
               else
                  $DestType = 'varchar(255)';
               $Mappings[$Column] = $DestColumn;
            }
         } elseif(array_key_exists($Column, $Structure)) {
            $DestColumn = $Column;
            $DestType = $Structure[$Column];
         } else {
            $DestColumn = '';
            $DestType = '';
         }

         // Check to see if we have to add the column to the export structure.
         if($DestColumn && !array_key_exists($DestColumn, $ExportStructure)) {
            // TODO: Make sure $DestType is a valid MySQL type.
            $ExportStructure[$DestColumn] = $DestType;
         }
      }

      // Add filtered mappings since filters can add new columns.
      foreach ($Mappings as $Source => $Options) {
         if (!is_array($Options) || !isset($Options['Column']))
            continue;
         $DestColumn = $Options['Column'];
         if (isset($ExportStructure[$DestColumn]))
            continue;

         if (isset($Structure[$DestColumn]))
            $DestType = $Structure[$DestColumn];
         elseif (isset($Options['Type']))
            $DestType = $Options['Type'];
         else
            continue;

         $ExportStructure[$DestColumn] = $DestType;
         $Mappings[$Source] = $DestColumn;
      }

      return $ExportStructure;
   }
   
   public function GetQueryStructure($Query, $Key = FALSE) {
      $QueryStruct = rtrim($Query, ';').' limit 1';
      if (!$Key)
         $Key = md5($QueryStruct);
      if (isset($this->_QueryStructures[$Key]))
         return $this->_QueryStructures[$Key];
      
      $R = $this->Query($QueryStruct, TRUE);
      $i = 0;
      $Result = array();
      while ($i < mysql_num_fields($R)) {
         $Meta = mysql_fetch_field($R, $i);
         $Result[$Meta->name] = $Meta->table;
         $i++;
      }
      $this->_QueryStructures[$Key] = $Result;
      return $Result;
   }

   protected function _GetTableHeader($Structure, $GlobalStructure) {
      $TableHeader = '';

      foreach($Structure as $Column => $Type) {
         if(strlen($TableHeader) > 0)
            $TableHeader .= self::DELIM;
         if(array_key_exists($Column, $GlobalStructure)) {
            $TableHeader .= $Column;
         } else {
            $TableHeader .= $Column.':'.$Type;
         }
      }
      return $TableHeader;
   }
   
   public function HasFilter(&$Mappings) {
      foreach ($Mappings as $Column => $Info) {
         if (is_array($Info) && isset($Info['Filter'])) {
            return TRUE;
         }
      }
      return FALSE;
   }
   
   /**
    * Decode the HTML out of a value.
    */
   public function HTMLDecoder($Value) {
      return html_entity_decode($Value, ENT_QUOTES, 'UTF-8');
   }
   
   public function HTMLDecoderDb($TableName, $ColumnName, $PK) {
      $Common = array('&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&apos;' => "'", '&quot;' => '"', '&#39;' => "'");
      foreach ($Common as $From => $To) {
         $FromQ = mysql_escape_string($From);
         $ToQ = mysql_escape_string($To);
         $Sql = "update :_{$TableName} set $ColumnName = replace($ColumnName, '$FromQ', '$ToQ') where $ColumnName like '%$FromQ%'";
         
         $this->Query($Sql);
      }
      
      // Now decode the remaining rows.
      $Sql = "select * from :_$TableName where $ColumnName like '%&%;%'";
      $Result = $this->Query($Sql, TRUE);
      while ($Row = mysql_fetch_assoc($Result)) {
         $From = $Row[$ColumnName];
         $To = $this->HTMLDecoder($From);
         
         if ($From != $To) {
            $ToQ = mysql_escape_string($To);
            $Sql = "update :_{$TableName} set $ColumnName = '$ToQ' where $PK = {$Row[$PK]}";
            $this->Query($Sql, TRUE);
         }
      }
      
   }

    /**
    * vBulletin needs some fields decoded and it won't hurt the others.
    */
//   public function HTMLDecoder($Table, $Field, $Value) {
//      if(($Table == 'Category' || $Table == 'Discussion') && $Field == 'Name')
//         return html_entity_decode($Value);
//      else
//         return $Value;
//   }


   protected function _OpenFile() {
      if($this->UseStreaming) {
         /** Setup the output to stream the file. */

         // required for IE, otherwise Content-Disposition may be ignored
         if(ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');

         @ob_end_clean();

         
         $fp = fopen('php://output', 'ab');
         header("Content-Disposition: attachment; filename=\"{$this->Path}\"");
         header('Content-Type: text/plain');
         header("Content-Transfer-Encoding: binary");
         header('Accept-Ranges: bytes');
         header("Cache-control: private");
         header('Pragma: private');
         header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      } else {
         $this->Path = str_replace(' ', '_', $this->Path);
         if($this->UseCompression())
            $fp = gzopen($this->Path, 'wb');
         else
            $fp = fopen($this->Path, 'wb');
      }
      $this->_File = $fp;
      return $fp;
   }

   /** Execute a SQL query on the current connection.
    *
    * @param string $Query The sql to execute.
    * @return resource The query cursor.
    */
   public function Query($Query, $Buffer = FALSE) {
      if (isset($this->_LastResult) && is_resource($this->_LastResult))
         mysql_free_result($this->_LastResult);
      $Query = str_replace(':_', $this->Prefix, $Query); // replace prefix.
      if ($this->SourcePrefix) {
         $Query = preg_replace("`\b{$this->SourcePrefix}`", $this->Prefix, $Query); // replace prefix.
      }
      
      $Query = rtrim($Query, ';').';';
      
      if (!preg_match('`limit 1;$`', $Query))
         $this->Queries[] = $Query;
         
      if ($this->Destination == 'database' && $this->CaptureOnly) {
         if (!preg_match('`^\s*select|show|describe`', $Query))
            return 'SKIPPED';
      }

      $Connection = mysql_connect($this->_Host, $this->_Username, $this->_Password);
      mysql_select_db($this->_DbName);
      mysql_query("set names {$this->CharacterSet}");
      if ($Buffer)
         $Result = mysql_query($Query, $Connection);
      else {
         $Result = mysql_unbuffered_query($Query, $Connection);
         if (is_resource($Result))
            $this->_LastResult = $Result;
      }

      if ($Result === FALSE) {
         echo '<pre>', htmlspecialchars($Query), '</pre>';
         trigger_error(mysql_error($Connection));
      }
      
      return $Result;
   }
   
   public function QueryN($SqlList) {
      if (!is_array($SqlList))
         $SqlList = explode(';', $SqlList);
      
      foreach ($SqlList as $Sql) {
         $Sql = trim($Sql);
         if ($Sql)
            $this->Query($Sql);
      }
   }
   
   public function SetConnection($Host = NULL, $Username = NULL, $Password = NULL, $DbName = NULL) {
      $this->_Host = $Host;
      $this->_Username = $Username;
      $this->_Password = $Password;
      $this->_DbName = $DbName;
   }

   /**
    * Returns an array of all the expected export tables and expected columns in the exports.
    * When exporting tables using ExportTable() all of the columns in this structure will always be exported in the order here, regardless of how their order in the query.
    * @return array
    * @see vnExport::ExportTable()
    */
   public function Structures() {
      return $this->_Structures;
   }
   
   public function TimestampToDate($Value) {
      if ($Value == NULL)
         return NULL;
      else
         return gmdate('Y-m-d H:i:s', $Value);
   }
   
   public function TimestampToDateDb($Value) {
      
   }

   /**
    * Whether or not to use compression on the output file.
    * @param bool $Value The value to set or NULL to just return the value.
    * @return bool
    */
   public function UseCompression($Value = NULL) {
      if($Value !== NULL)
         $this->_UseCompression = $Value;

      return $this->_UseCompression && $this->Destination == 'file' && !$this->UseStreaming && function_exists('gzopen');
   }

   /**
    * Returns the version of export file that will be created with this export.
    * The version is used when importing to determine the format of this file.
    * @return string
    */
   public function Version() {
      return APPLICATION_VERSION;
   }

   /**
    * Checks whether or not a table and columns exist in the database.
    *
    * @param string $Table The name of the table to check.
    * @param array $Columns An array of column names to check.
    * @return bool|array The method will return one of the following
    *  - true: If table and all of the columns exist.
    *  - false: If the table does not exist.
    *  - array: The names of the missing columns if one or more columns don't exist.
    */
   public function Exists($Table, $Columns = array()) {
      static $_Exists = array();
      
      if (!isset($_Exists[$Table])) {
         $Result = $this->Query("show table status like ':_$Table'", TRUE);
         if (!$Result) {
            $_Exists[$Table] = FALSE;
         } elseif (!mysql_fetch_assoc($Result)) {
            $_Exists[$Table] = FALSE;
         } else {
            mysql_free_result($Result);
            $Desc = $this->Query('describe :_'.$Table);
            if ($Desc === false) {
               $_Exists[$Table] = FALSE;
            } else {
               if (is_string($Desc))
                  die($Desc);
               
               $Cols = array();
               while (($TD = mysql_fetch_assoc($Desc)) !== false) {
                  $Cols[$TD['Field']] = $TD;
               }
               mysql_free_result($Desc);
               $_Exists[$Table] = $Cols;
            }
         }
      }
      
      if ($_Exists[$Table] == FALSE)
         return FALSE;
      
      $Columns = (array)$Columns;
      
      if (count($Columns) == 0)
         return true;
      
      $Missing = array();
      $Cols = array_keys($_Exists[$Table]);
      foreach ($Columns as $Column) {
         if (!in_array($Column, $Cols))
            $Missing[] = $Column;
      }
      return count($Missing) == 0 ? true : $Missing;
   }

   /**
    * Checks all required source tables are present
    */
   public function VerifySource($RequiredTables) {
      $MissingTables = false;
      $CountMissingTables = 0;
      $MissingColumns = array();

      foreach($RequiredTables as $ReqTable => $ReqColumns) {
         $TableDescriptions = $this->Query('describe :_'.$ReqTable);
         //echo 'describe '.$Prefix.$ReqTable;
         if($TableDescriptions === false) { // Table doesn't exist
            $CountMissingTables++;
            if($MissingTables !== false)
               $MissingTables .= ', '.$ReqTable;
            else
               $MissingTables = $ReqTable;
         }
         else {
            // Build array of columns in this table
            $PresentColumns = array();
            while (($TD = mysql_fetch_assoc($TableDescriptions)) !== false) {
               $PresentColumns[] = $TD['Field'];
            }
            // Compare with required columns
            foreach($ReqColumns as $ReqCol) {
               if(!in_array($ReqCol, $PresentColumns))
                  $MissingColumns[$ReqTable][] = $ReqCol;
            }

            mysql_free_result($TableDescriptions);
         }
      }

      // Return results
      if($MissingTables === false) {
         if(count($MissingColumns) > 0) {
            $Result = array();

            // Build a string of missing columns.
            foreach ($MissingColumns as $Table => $Columns) {
               $Result[] = "The $Table table is missing the following column(s): ".implode(', ', $Columns);
            }
            return implode("<br />\n", $Result);
         }
         else return true; // Nothing missing!
      }
      elseif($CountMissingTables == count($RequiredTables)) {
         $Result = 'The required tables are not present in the database. Make sure you entered the correct database name and prefix and try again.';

         // Guess the prefixes to notify the user.
         $Prefixes = $this->GetDatabasePrefixes();
         if (count($Prefixes) == 1)
            $Result .= ' Based on the database you provided, your database prefix is probably '.implode(', ', $Prefixes);
         elseif (count($Prefixes) > 0)
            $Result .= ' Based on the database you provided, your database prefix is probably one of the following: '.implode(', ', $Prefixes);

         return $Result;
      }
      else {
         return 'Missing required database tables: '.$MissingTables;
      }
   }

   public function WriteBeginTable($fp, $TableName, $Columns) {
      $TableHeader = '';

      foreach($Columns as $Key => $Value) {
         if (is_numeric($Key)) {
            $Column = $Value;
            $Type = '';
         } else {
            $Column = $Key;
            $Type = $Value;
         }

         if(strlen($TableHeader) > 0)
            $TableHeader .= self::DELIM;

         if ($Type)
            $TableHeader .= $Column.':'.$Type;
         else
            $TableHeader .= $Column;
      }

      fwrite($fp, 'Table: '.$TableName.self::NEWLINE);
      fwrite($fp, $TableHeader.self::NEWLINE);
      
   }
   
   public static function FileExtension($ColumnName) {
      return "right($ColumnName, instr(reverse($ColumnName), '.'))";
   }
   
   public function UrlDecode($Value) {
      return urldecode($Value);
   }
}
?>