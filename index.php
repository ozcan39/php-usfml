<?php
$GLOBALS['sessions']=array();//the array of session number which belongs to the database we are working on
$GLOBALS['searches']=array();//the array of user searches

//analyzes and saves the session numbers in each log row which belongs to the database we work on
//this analysis is necessary for indentifying all queries made in database we work on
function target_db_sessions_identify($row,$db)
{
    //a sample of row: 343843 Init DB database_name --> session number: 343843
    //a sample of row: 180802 0:21:24 343881 Connect root@localhost on database_name --> session number: 180802
    if(preg_match('/\?^.*|(\d+) (Connect|Init).*('.$db.'$)/',$row,$data))
    {
        $GLOBALS['sessions'][]=$data[1];//assigns session number

        return TRUE;
    }
    else { return FALSE; }
}

//analyzes the session number and search phrase of each row of mysql log
function query_identify($row,$target_table,$column_name)
{
    //a sample of row: 343887 Query select * from target_table where column_name like '%search_phrase%' --> session number: 343887
    //a sample of row: 180802 Query select * from target_table where column_name2 like '%search_phrase%' or column_name like '%search_phrase%' --> session number: 180802
    if(preg_match('/\?^.*|(\d+).*Query.*select.*'.$target_table.'.*'.$column_name.'.*like.*(\'|\")(.*)(\'|\").*$/i',$row,$data))
    {
        //cleaning % character from the search_phrase
        $search=trim(mb_strtolower(str_replace('%','',$data[3]),"UTF-8"));

        //array(session_number,search_phrase)
        return array($data[1],$search);
    }
    else { return FALSE; }
}

//analyzes user searches in mysql log file
function user_search_analysis($mysql_log_file,$target_database,$target_table,$column_name)
{
    $file = new SplFileObject($mysql_log_file);
    while(!$file->eof())
    {
        $row=$file->fgets();

        //if the row does not have a connection data to the database we are working on
        if(!target_db_sessions_identify($row,$target_database))
        {
            //then we'll check whether or not the row has a query data for user searches
            $check=query_identify($row,$target_table,$column_name);
            if($check!=FALSE)//if the row has a query data
            {
                //if the session number of this query exits in the array of session numbers which belongs to the database we are working on
                //the reason why it is done, is because there may be similar table name in different database
                if(in_array($check[0],$GLOBALS['sessions']))
                {
                    //if the search phrase was recorded before
                    if(in_array($check[1],$GLOBALS['searches']))
                    {
                        $GLOBALS['searches'][$check[1]]['total_search']++; //the count of this search increases
                    }
                    else
                    {
                        $GLOBALS['searches'][$check[1]]['total_search']=1; //the search phrase and count of this search phrase are created
                        $GLOBALS['searches'][$check[1]]['word_count']=count(explode(' ',$check[1])); //the word count of search phrase
                    }
                }
            }
        }

        //else {} in this case, this row has a connection data anyway and target_db_sessions_identify function checks the database name
        //and if the database name is target_database then session number will be saved and then this row will be skipped
    }
    $file = null;
}

##########################################################################
##########################################################################
##########################################################################
#######################triggering for analysis############################

$mysql_log_file="..."; // path to mysql log file
$target_database_name="..."; // which database will be analyzed
$target_table_name="..."; // which table will be analyzed in $target_database_name
$column_name="..."; // which column will be investigated in $target_table_name

//analyzing user searches
user_search_analysis($mysql_log_file,$target_database_name,$target_table_name,$column_name);

//this data can be saved in any database
var_dump($GLOBALS['searches']);
?>