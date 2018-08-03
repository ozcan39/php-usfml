# php-usfml
This code analyzes mysql log file for detecting web user searches. It was used to improve usability of a website by detecting user site-in searches.

It checks queries which made by users and finds out search phrases with word counts.

Search phrases with word count can be used for identifying user interests. Hereby some updates which improve usability of website, can be made.

# How i used
I used this code with one day logrotated mysql log. Before anaylzing, daily mysql log was packaged (every day at 00:00 AM) and then this code was run with a cron trigger at 03:00 AM for analyzing packaged mysql log. For mysql logrotate, the configurations below used.

```
var/log/mysql/mysql.log {

daily

rotate 1

missing ok

create 644 mysql mysql

nocompress

.

.

.
```

After analyzing, results saved in SQlite database and then used for improving usability of a website.

The gap between packaging mysql log and running the code depends on system features.

# Before starting to analyze
The name of database, table and column are required as well as mysql log file path.

max_execution_time in php.ini file may need to be increased. Because, timeout may be occurred while analyzing.

# Explanations of the functions in the code
There are 3 functions created in the code.

`1` For analyzing and saving the session numbers in each log row which belongs to the database we work on. This analysis is necessary for indentifying all queries made in database we work on.
```php
function target_db_sessions_identify($row,$db) { ... }
```

`2` For analyzing the session number and search phrase of each row of mysql log.
```php
function query_identify($row,$target_table,$column_name) { ... }
```

`3` The main function => for analyzing user searches in mysql log file.
```php
function user_search_analysis($mysql_log_file,$target_database,$target_table,$column_name) { ... }
```

The function 3 uses the other ones.

# What kind of sql commands are analyzed
While identifying user searches, this code uses regular expression (REGEX) in function 2. REGEX analyzes sql command similar to as shown below and detects search_phrases:

`1` select * from target_table where column_name2 like '%search_phrase%' or column_name like '%search_phrase%'

`2` select * from target_table where column_name like '%search_phrase%'

If different kind of sql commands are used, then changing the REGEX rule will be enough. The other processes does not need to be changed.

# How to run
For running the code and detecting user searches in mysql log file, it is enough to call the function 3 as shown below:

```php
$mysql_log_file="..."; // path to mysql log file
$target_database_name="..."; // which database will be analyzed
$target_table_name="..."; // which table will be analyzed in $target_database_name
$column_name="..."; // which column will be investigated in $target_table_name

//analyzing user searches
user_search_analysis($mysql_log_file,$target_database_name,$target_table_name,$column_name);
```

# How results come out
Analyzed data saved in `$GLOBALS['searches']` variable.

```php
var_dump($GLOBALS['searches']); // for reaching the results

//results
array (size=3)
  'search phrase 1' => 
    array (size=2)
      'total_search' => int 5
      'word_count' => int 3
  'search phrase2' => 
    array (size=2)
      'total_search' => int 10
      'word_count' => int 2
  'search phr ase 3' => 
    array (size=2)
      'total_search' => int 15
      'word_count' => int 4
```

The results can be saved in any database or however you want to use..


