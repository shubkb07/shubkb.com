var e={information_for_contributors:["This file has been converted from https://github.com/microsoft/vscode-mssql/blob/master/syntaxes/SQL.plist","If you want to provide a fix or improvement, please create a pull request against the original repository.","Once accepted there, we are happy to receive an update request."],version:"https://github.com/microsoft/vscode-mssql/commit/9cb3529a978ddf599bf5bdd228f21bbcfe2914f5",name:"sql",scopeName:"source.sql",patterns:[{match:"((?<!@)@)\\b(\\w+)\\b",name:"text.variable"},{match:"(\\[)[^\\]]*(\\])",name:"text.bracketed"},{include:"#comments"},{captures:{1:{name:"keyword.other.create.sql"},2:{name:"keyword.other.sql"},5:{name:"entity.name.function.sql"}},match:"(?i:^\\s*(create(?:\\s+or\\s+replace)?)\\s+(aggregate|conversion|database|domain|function|group|(unique\\s+)?index|language|operator class|operator|rule|schema|sequence|table|tablespace|trigger|type|user|view)\\s+)(['\"`]?)(\\w+)\\4",name:"meta.create.sql"},{captures:{1:{name:"keyword.other.create.sql"},2:{name:"keyword.other.sql"}},match:"(?i:^\\s*(drop)\\s+(aggregate|conversion|database|domain|function|group|index|language|operator class|operator|rule|schema|sequence|table|tablespace|trigger|type|user|view))",name:"meta.drop.sql"},{captures:{1:{name:"keyword.other.create.sql"},2:{name:"keyword.other.table.sql"},3:{name:"entity.name.function.sql"},4:{name:"keyword.other.cascade.sql"}},match:"(?i:\\s*(drop)\\s+(table)\\s+(\\w+)(\\s+cascade)?\\b)",name:"meta.drop.sql"},{captures:{1:{name:"keyword.other.create.sql"},2:{name:"keyword.other.table.sql"}},match:"(?i:^\\s*(alter)\\s+(aggregate|conversion|database|domain|function|group|index|language|operator class|operator|proc(edure)?|rule|schema|sequence|table|tablespace|trigger|type|user|view)\\s+)",name:"meta.alter.sql"},{captures:{1:{name:"storage.type.sql"},2:{name:"storage.type.sql"},3:{name:"constant.numeric.sql"},4:{name:"storage.type.sql"},5:{name:"constant.numeric.sql"},6:{name:"storage.type.sql"},7:{name:"constant.numeric.sql"},8:{name:"constant.numeric.sql"},9:{name:"storage.type.sql"},10:{name:"constant.numeric.sql"},11:{name:"storage.type.sql"},12:{name:"storage.type.sql"},13:{name:"storage.type.sql"},14:{name:"constant.numeric.sql"},15:{name:"storage.type.sql"}},match:"(?xi)\n\n\t\t\t\t# normal stuff, capture 1\n\t\t\t\t \\b(bigint|bigserial|bit|boolean|box|bytea|cidr|circle|date|double\\sprecision|inet|int|integer|line|lseg|macaddr|money|oid|path|point|polygon|real|serial|smallint|sysdate|text)\\b\n\n\t\t\t\t# numeric suffix, capture 2 + 3i\n\t\t\t\t|\\b(bit\\svarying|character\\s(?:varying)?|tinyint|var\\schar|float|interval)\\((\\d+)\\)\n\n\t\t\t\t# optional numeric suffix, capture 4 + 5i\n\t\t\t\t|\\b(char|number|varchar\\d?)\\b(?:\\((\\d+)\\))?\n\n\t\t\t\t# special case, capture 6 + 7i + 8i\n\t\t\t\t|\\b(numeric|decimal)\\b(?:\\((\\d+),(\\d+)\\))?\n\n\t\t\t\t# special case, captures 9, 10i, 11\n\t\t\t\t|\\b(times?)\\b(?:\\((\\d+)\\))?(\\swith(?:out)?\\stime\\szone\\b)?\n\n\t\t\t\t# special case, captures 12, 13, 14i, 15\n\t\t\t\t|\\b(timestamp)(?:(s|tz))?\\b(?:\\((\\d+)\\))?(\\s(with|without)\\stime\\szone\\b)?\n\n\t\t\t"},{match:"(?i:\\b((?:primary|foreign)\\s+key|references|on\\sdelete(\\s+cascade)?|nocheck|check|constraint|collate|default)\\b)",name:"storage.modifier.sql"},{match:"\\b\\d+\\b",name:"constant.numeric.sql"},{match:"(?i:\\b(select(\\s+(all|distinct))?|insert\\s+(ignore\\s+)?into|update|delete|from|set|where|group\\s+by|or|like|and|union(\\s+all)?|having|order\\s+by|limit|cross\\s+join|join|straight_join|(inner|(left|right|full)(\\s+outer)?)\\s+join|natural(\\s+(inner|(left|right|full)(\\s+outer)?))?\\s+join)\\b)",name:"keyword.other.DML.sql"},{match:"(?i:\\b(on|off|((is\\s+)?not\\s+)?null)\\b)",name:"keyword.other.DDL.create.II.sql"},{match:"(?i:\\bvalues\\b)",name:"keyword.other.DML.II.sql"},{match:"(?i:\\b(begin(\\s+work)?|start\\s+transaction|commit(\\s+work)?|rollback(\\s+work)?)\\b)",name:"keyword.other.LUW.sql"},{match:"(?i:\\b(grant(\\swith\\sgrant\\soption)?|revoke)\\b)",name:"keyword.other.authorization.sql"},{match:"(?i:\\bin\\b)",name:"keyword.other.data-integrity.sql"},{match:"(?i:^\\s*(comment\\s+on\\s+(table|column|aggregate|constraint|database|domain|function|index|operator|rule|schema|sequence|trigger|type|view))\\s+.*?\\s+(is)\\s+)",name:"keyword.other.object-comments.sql"},{match:"(?i)\\bAS\\b",name:"keyword.other.alias.sql"},{match:"(?i)\\b(DESC|ASC)\\b",name:"keyword.other.order.sql"},{match:"\\*",name:"keyword.operator.star.sql"},{match:"[!<>]?=|<>|<|>",name:"keyword.operator.comparison.sql"},{match:"-|\\+|/",name:"keyword.operator.math.sql"},{match:"\\|\\|",name:"keyword.operator.concatenator.sql"},{match:"(?i)\\b(approx_count_distinct|approx_percentile_cont|approx_percentile_disc|avg|checksum_agg|count|count_big|group|grouping|grouping_id|max|min|sum|stdev|stdevp|var|varp)\\b\\s*\\(",captures:{1:{name:"support.function.aggregate.sql"}}},{match:"(?i)\\b(cume_dist|first_value|lag|last_value|lead|percent_rank|percentile_cont|percentile_disc)\\b\\s*\\(",captures:{1:{name:"support.function.analytic.sql"}}},{match:"(?i)\\b(bit_count|get_bit|left_shift|right_shift|set_bit)\\b\\s*\\(",captures:{1:{name:"support.function.bitmanipulation.sql"}}},{match:"(?i)\\b(cast|convert|parse|try_cast|try_convert|try_parse)\\b\\s*\\(",captures:{1:{name:"support.function.conversion.sql"}}},{match:"(?i)\\b(collationproperty|tertiary_weights)\\b\\s*\\(",captures:{1:{name:"support.function.collation.sql"}}},{match:"(?i)\\b(asymkey_id|asymkeyproperty|certproperty|cert_id|crypt_gen_random|decryptbyasymkey|decryptbycert|decryptbykey|decryptbykeyautoasymkey|decryptbykeyautocert|decryptbypassphrase|encryptbyasymkey|encryptbycert|encryptbykey|encryptbypassphrase|hashbytes|is_objectsigned|key_guid|key_id|key_name|signbyasymkey|signbycert|symkeyproperty|verifysignedbycert|verifysignedbyasymkey)\\b\\s*\\(",captures:{1:{name:"support.function.cryptographic.sql"}}},{match:"(?i)\\b(cursor_status)\\b\\s*\\(",captures:{1:{name:"support.function.cursor.sql"}}},{match:"(?i)\\b(sysdatetime|sysdatetimeoffset|sysutcdatetime|current_time(stamp)?|getdate|getutcdate|datename|datepart|day|month|year|datefromparts|datetime2fromparts|datetimefromparts|datetimeoffsetfromparts|smalldatetimefromparts|timefromparts|datediff|dateadd|datetrunc|eomonth|switchoffset|todatetimeoffset|isdate|date_bucket)\\b\\s*\\(",captures:{1:{name:"support.function.datetime.sql"}}},{match:"(?i)\\b(datalength|ident_current|ident_incr|ident_seed|identity|sql_variant_property)\\b\\s*\\(",captures:{1:{name:"support.function.datatype.sql"}}},{match:"(?i)\\b(coalesce|nullif)\\b\\s*\\(",captures:{1:{name:"support.function.expression.sql"}}},{match:"(?<!@)@@(?i)\\b(cursor_rows|connections|cpu_busy|datefirst|dbts|error|fetch_status|identity|idle|io_busy|langid|language|lock_timeout|max_connections|max_precision|nestlevel|options|packet_errors|pack_received|pack_sent|procid|remserver|rowcount|servername|servicename|spid|textsize|timeticks|total_errors|total_read|total_write|trancount|version)\\b\\s*\\(",captures:{1:{name:"support.function.globalvar.sql"}}},{match:"(?i)\\b(json|isjson|json_object|json_array|json_value|json_query|json_modify|json_path_exists)\\b\\s*\\(",captures:{1:{name:"support.function.json.sql"}}},{match:"(?i)\\b(choose|iif|greatest|least)\\b\\s*\\(",captures:{1:{name:"support.function.logical.sql"}}},{match:"(?i)\\b(abs|acos|asin|atan|atn2|ceiling|cos|cot|degrees|exp|floor|log|log10|pi|power|radians|rand|round|sign|sin|sqrt|square|tan)\\b\\s*\\(",captures:{1:{name:"support.function.mathematical.sql"}}},{match:"(?i)\\b(app_name|applock_mode|applock_test|assemblyproperty|col_length|col_name|columnproperty|database_principal_id|databasepropertyex|db_id|db_name|file_id|file_idex|file_name|filegroup_id|filegroup_name|filegroupproperty|fileproperty|fulltextcatalogproperty|fulltextserviceproperty|index_col|indexkey_property|indexproperty|object_definition|object_id|object_name|object_schema_name|objectproperty|objectpropertyex|original_db_name|parsename|schema_id|schema_name|scope_identity|serverproperty|stats_date|type_id|type_name|typeproperty)\\b\\s*\\(",captures:{1:{name:"support.function.metadata.sql"}}},{match:"(?i)\\b(rank|dense_rank|ntile|row_number)\\b\\s*\\(",captures:{1:{name:"support.function.ranking.sql"}}},{match:"(?i)\\b(generate_series|opendatasource|openjson|openrowset|openquery|openxml|predict|string_split)\\b\\s*\\(",captures:{1:{name:"support.function.rowset.sql"}}},{match:"(?i)\\b(certencoded|certprivatekey|current_user|database_principal_id|has_perms_by_name|is_member|is_rolemember|is_srvrolemember|original_login|permissions|pwdcompare|pwdencrypt|schema_id|schema_name|session_user|suser_id|suser_sid|suser_sname|system_user|suser_name|user_id|user_name)\\b\\s*\\(",captures:{1:{name:"support.function.security.sql"}}},{match:"(?i)\\b(ascii|char|charindex|concat|difference|format|left|len|lower|ltrim|nchar|nodes|patindex|quotename|replace|replicate|reverse|right|rtrim|soundex|space|str|string_agg|string_escape|string_split|stuff|substring|translate|trim|unicode|upper)\\b\\s*\\(",captures:{1:{name:"support.function.string.sql"}}},{match:"(?i)\\b(binary_checksum|checksum|compress|connectionproperty|context_info|current_request_id|current_transaction_id|decompress|error_line|error_message|error_number|error_procedure|error_severity|error_state|formatmessage|get_filestream_transaction_context|getansinull|host_id|host_name|isnull|isnumeric|min_active_rowversion|newid|newsequentialid|rowcount_big|session_context|session_id|xact_state)\\b\\s*\\(",captures:{1:{name:"support.function.system.sql"}}},{match:"(?i)\\b(patindex|textptr|textvalid)\\b\\s*\\(",captures:{1:{name:"support.function.textimage.sql"}}},{captures:{1:{name:"constant.other.database-name.sql"},2:{name:"constant.other.table-name.sql"}},match:"(\\w+?)\\.(\\w+)"},{include:"#strings"},{include:"#regexps"},{match:"\\b(?i)(abort|abort_after_wait|absent|absolute|accent_sensitivity|acceptable_cursopt|acp|action|activation|add|address|admin|aes_128|aes_192|aes_256|affinity|after|aggregate|algorithm|all_constraints|all_errormsgs|all_indexes|all_levels|all_results|allow_connections|allow_dup_row|allow_encrypted_value_modifications|allow_page_locks|allow_row_locks|allow_snapshot_isolation|alter|altercolumn|always|anonymous|ansi_defaults|ansi_null_default|ansi_null_dflt_off|ansi_null_dflt_on|ansi_nulls|ansi_padding|ansi_warnings|appdomain|append|application|apply|arithabort|arithignore|array|assembly|asymmetric|asynchronous_commit|at|atan2|atomic|attach|attach_force_rebuild_log|attach_rebuild_log|audit|auth_realm|authentication|auto|auto_cleanup|auto_close|auto_create_statistics|auto_drop|auto_shrink|auto_update_statistics|auto_update_statistics_async|automated_backup_preference|automatic|autopilot|availability|availability_mode|backup|backup_priority|base64|basic|batches|batchsize|before|between|bigint|binary|binding|bit|block|blockers|blocksize|bmk|both|break|broker|broker_instance|bucket_count|buffer|buffercount|bulk_logged|by|call|caller|card|case|catalog|catch|cert|certificate|change_retention|change_tracking|change_tracking_context|changes|char|character|character_set|check_expiration|check_policy|checkconstraints|checkindex|checkpoint|checksum|cleanup_policy|clear|clear_port|close|clustered|codepage|collection|column_encryption_key|column_master_key|columnstore|columnstore_archive|colv_80_to_100|colv_100_to_80|commit_differential_base|committed|compatibility_level|compress_all_row_groups|compression|compression_delay|concat_null_yields_null|concatenate|configuration|connect|containment|continue|continue_after_error|contract|contract_name|control|conversation|conversation_group_id|conversation_handle|copy|copy_only|count_rows|counter|create(\\\\s+or\\\\s+alter)?|credential|cross|cryptographic|cryptographic_provider|cube|cursor|cursor_close_on_commit|cursor_default|data|data_compression|data_flush_interval_seconds|data_mirroring|data_purity|data_source|database|database_name|database_snapshot|datafiletype|date_correlation_optimization|date|datefirst|dateformat|date_format|datetime|datetime2|datetimeoffset|day(s)?|db_chaining|dbid|dbidexec|dbo_only|deadlock_priority|deallocate|dec|decimal|declare|decrypt|decrypt_a|decryption|default_database|default_fulltext_language|default_language|default_logon_domain|default_schema|definition|delay|delayed_durability|delimitedtext|density_vector|dependent|des|description|desired_state|desx|differential|digest|disable|disable_broker|disable_def_cnst_chk|disabled|disk|distinct|distributed|distribution|drop|drop_existing|dts_buffers|dump|durability|dynamic|edition|elements|else|emergency|empty|enable|enable_broker|enabled|encoding|encrypted|encrypted_value|encryption|encryption_type|end|endpoint|endpoint_url|enhancedintegrity|entry|error_broker_conversations|errorfile|estimateonly|event|except|exec|executable|execute|exists|expand|expiredate|expiry_date|explicit|external|external_access|failover|failover_mode|failure_condition_level|fast|fast_forward|fastfirstrow|federated_service_account|fetch|field_terminator|fieldterminator|file|filelistonly|filegroup|filegrowth|filename|filestream|filestream_log|filestream_on|filetable|file_format|filter|first_row|fips_flagger|fire_triggers|first|firstrow|float|flush_interval_seconds|fmtonly|following|for|force|force_failover_allow_data_loss|force_service_allow_data_loss|forced|forceplan|formatfile|format_options|format_type|formsof|forward_only|free_cursors|free_exec_context|fullscan|fulltext|fulltextall|fulltextkey|function|generated|get|geography|geometry|global|go|goto|governor|guid|hadoop|hardening|hash|hashed|header_limit|headeronly|health_check_timeout|hidden|hierarchyid|histogram|histogram_steps|hits_cursors|hits_exec_context|hour(s)?|http|identity|identity_value|if|ifnull|ignore|ignore_constraints|ignore_dup_key|ignore_dup_row|ignore_triggers|image|immediate|implicit_transactions|include|include_null_values|incremental|index|inflectional|init|initiator|insensitive|insert|instead|int|integer|integrated|intersect|intermediate|interval_length_minutes|into|inuse_cursors|inuse_exec_context|io|is|isabout|iso_week|isolation|job_tracker_location|json|keep|keep_nulls|keep_replication|keepdefaults|keepfixed|keepidentity|keepnulls|kerberos|key|key_path|key_source|key_store_provider_name|keyset|kill|kilobytes_per_batch|labelonly|langid|language|last|lastrow|leading|legacy_cardinality_estimation|length|level|lifetime|lineage_80_to_100|lineage_100_to_80|listener_ip|listener_port|load|loadhistory|lob_compaction|local|local_service_name|locate|location|lock_escalation|lock_timeout|lockres|log|login|login_type|loop|manual|mark_in_use_for_removal|masked|master|matched|max_queue_readers|max_duration|max_outstanding_io_per_volume|maxdop|maxerrors|maxlength|maxtransfersize|max_plans_per_query|max_storage_size_mb|mediadescription|medianame|mediapassword|memogroup|memory_optimized|merge|message|message_forward_size|message_forwarding|microsecond|millisecond|minute(s)?|mirror_address|misses_cursors|misses_exec_context|mixed|modify|money|month|move|multi_user|must_change|name|namespace|nanosecond|native|native_compilation|nchar|ncharacter|nested_triggers|never|new_account|new_broker|newname|next|no|no_browsetable|no_checksum|no_compression|no_infomsgs|no_triggers|no_truncate|nocount|noexec|noexpand|noformat|noinit|nolock|nonatomic|nonclustered|nondurable|none|norecompute|norecovery|noreset|norewind|noskip|not|notification|nounload|now|nowait|ntext|ntlm|nulls|numeric|numeric_roundabort|nvarchar|object|objid|oem|offline|old_account|online|operation_mode|open|openjson|optimistic|option|orc|out|outer|output|over|override|owner|ownership|pad_index|page|page_checksum|page_verify|pagecount|paglock|param|parameter_sniffing|parameter_type_expansion|parameterization|parquet|parseonly|partial|partition|partner|password|path|pause|percentage|permission_set|persisted|period|physical_only|plan_forcing_mode|policy|pool|population|ports|preceding|precision|predicate|presume_abort|primary|primary_role|print|prior|priority |priority_level|private|proc(edure)?|procedure_name|profile|provider|quarter|query_capture_mode|query_governor_cost_limit|query_optimizer_hotfixes|query_store|queue|quoted_identifier|raiserror|range|raw|rcfile|rc2|rc4|rc4_128|rdbms|read_committed_snapshot|read|read_only|read_write|readcommitted|readcommittedlock|readonly|readpast|readuncommitted|readwrite|real|rebuild|receive|recmodel_70backcomp|recompile|reconfigure|recovery|recursive|recursive_triggers|redo_queue|reject_sample_value|reject_type|reject_value|relative|remote|remote_data_archive|remote_proc_transactions|remote_service_name|remove|removed_cursors|removed_exec_context|reorganize|repeat|repeatable|repeatableread|replace|replica|replicated|replnick_100_to_80|replnickarray_80_to_100|replnickarray_100_to_80|required|required_cursopt|resample|reset|resource|resource_manager_location|respect|restart|restore|restricted_user|resume|retaindays|retention|return|revert|rewind|rewindonly|returns|robust|role|rollup|root|round_robin|route|row|rowdump|rowguidcol|rowlock|row_terminator|rows|rows_per_batch|rowsets_only|rowterminator|rowversion|rsa_1024|rsa_2048|rsa_3072|rsa_4096|rsa_512|safe|safety|sample|save|scalar|schema|schemabinding|scoped|scroll|scroll_locks|sddl|second|secexpr|seconds|secondary|secondary_only|secondary_role|secret|security|securityaudit|selective|self|send|sent|sequence|serde_method|serializable|server|service|service_broker|service_name|service_objective|session_timeout|session|sessions|seterror|setopts|sets|shard_map_manager|shard_map_name|sharded|shared_memory|show_statistics|showplan_all|showplan_text|showplan_xml|showplan_xml_with_recompile|shrinkdb|shutdown|sid|signature|simple|single_blob|single_clob|single_nclob|single_user|singleton|site|size|size_based_cleanup_mode|skip|smalldatetime|smallint|smallmoney|snapshot|snapshot_import|snapshotrestorephase|soap|softnuma|sort_in_tempdb|sorted_data|sorted_data_reorg|spatial|sql|sql_bigint|sql_binary|sql_bit|sql_char|sql_date|sql_decimal|sql_double|sql_float|sql_guid|sql_handle|sql_longvarbinary|sql_longvarchar|sql_numeric|sql_real|sql_smallint|sql_time|sql_timestamp|sql_tinyint|sql_tsi_day|sql_tsi_frac_second|sql_tsi_hour|sql_tsi_minute|sql_tsi_month|sql_tsi_quarter|sql_tsi_second|sql_tsi_week|sql_tsi_year|sql_type_date|sql_type_time|sql_type_timestamp|sql_varbinary|sql_varchar|sql_variant|sql_wchar|sql_wlongvarchar|ssl|ssl_port|standard|standby|start|start_date|started|stat_header|state|statement|static|statistics|statistics_incremental|statistics_norecompute|statistics_only|statman|stats|stats_stream|status|stop|stop_on_error|stopat|stopatmark|stopbeforemark|stoplist|stopped|string_delimiter|subject|supplemental_logging|supported|suspend|symmetric|synchronous_commit|synonym|sysname|system|system_time|system_versioning|table|tableresults|tablock|tablockx|take|tape|target|target_index|target_partition|target_recovery_time|tcp|temporal_history_retention|text|textimage_on|then|thesaurus|throw|time|timeout|timestamp|tinyint|to|top|torn_page_detection|track_columns_updated|trailing|tran|transaction|transfer|transform_noise_words|triple_des|triple_des_3key|truncate|trustworthy|try|tsql|two_digit_year_cutoff|type|type_desc|type_warning|tzoffset|uid|unbounded|uncommitted|unique|uniqueidentifier|unlimited|unload|unlock|unsafe|updlock|url|use|useplan|useroptions|use_type_default|using|utcdatetime|valid_xml|validation|value|values|varbinary|varchar|verbose|verifyonly|version|view_metadata|virtual_device|visiblity|wait_at_low_priority|waitfor|webmethod|week|weekday|weight|well_formed_xml|when|while|widechar|widechar_ansi|widenative|window|windows|with|within|within group|witness|without|without_array_wrapper|workload|wsdl|xact_abort|xlock|xml|xmlschema|xquery|xsinil|year|zone)\\b",name:"keyword.other.sql"},{captures:{1:{name:"punctuation.section.scope.begin.sql"},2:{name:"punctuation.section.scope.end.sql"}},comment:"Allow for special ↩ behavior",match:"(\\()(\\))",name:"meta.block.sql"}],repository:{comments:{patterns:[{begin:"(^[ \\t]+)?(?=--)",beginCaptures:{1:{name:"punctuation.whitespace.comment.leading.sql"}},end:"(?!\\G)",patterns:[{begin:"--",beginCaptures:{0:{name:"punctuation.definition.comment.sql"}},end:"\\n",name:"comment.line.double-dash.sql"}]},{begin:"(^[ \\t]+)?(?=#)",beginCaptures:{1:{name:"punctuation.whitespace.comment.leading.sql"}},end:"(?!\\G)",patterns:[]},{include:"#comment-block"}]},"comment-block":{begin:"/\\*",captures:{0:{name:"punctuation.definition.comment.sql"}},end:"\\*/",name:"comment.block",patterns:[{include:"#comment-block"}]},regexps:{patterns:[{begin:"/(?=\\S.*/)",beginCaptures:{0:{name:"punctuation.definition.string.begin.sql"}},end:"/",endCaptures:{0:{name:"punctuation.definition.string.end.sql"}},name:"string.regexp.sql",patterns:[{include:"#string_interpolation"},{match:"\\\\/",name:"constant.character.escape.slash.sql"}]},{begin:"%r\\{",beginCaptures:{0:{name:"punctuation.definition.string.begin.sql"}},comment:"We should probably handle nested bracket pairs!?! -- Allan",end:"\\}",endCaptures:{0:{name:"punctuation.definition.string.end.sql"}},name:"string.regexp.modr.sql",patterns:[{include:"#string_interpolation"}]}]},string_escape:{match:"\\\\.",name:"constant.character.escape.sql"},string_interpolation:{captures:{1:{name:"punctuation.definition.string.begin.sql"},3:{name:"punctuation.definition.string.end.sql"}},match:"(#\\{)([^\\}]*)(\\})",name:"string.interpolated.sql"},strings:{patterns:[{captures:{2:{name:"punctuation.definition.string.begin.sql"},3:{name:"punctuation.definition.string.end.sql"}},comment:"this is faster than the next begin/end rule since sub-pattern will match till end-of-line and SQL files tend to have very long lines.",match:"(N)?(')[^']*(')",name:"string.quoted.single.sql"},{begin:"'",beginCaptures:{0:{name:"punctuation.definition.string.begin.sql"}},end:"'",endCaptures:{0:{name:"punctuation.definition.string.end.sql"}},name:"string.quoted.single.sql",patterns:[{include:"#string_escape"}]},{captures:{1:{name:"punctuation.definition.string.begin.sql"},2:{name:"punctuation.definition.string.end.sql"}},comment:"this is faster than the next begin/end rule since sub-pattern will match till end-of-line and SQL files tend to have very long lines.",match:"(`)[^`\\\\]*(`)",name:"string.quoted.other.backtick.sql"},{begin:"`",beginCaptures:{0:{name:"punctuation.definition.string.begin.sql"}},end:"`",endCaptures:{0:{name:"punctuation.definition.string.end.sql"}},name:"string.quoted.other.backtick.sql",patterns:[{include:"#string_escape"}]},{captures:{1:{name:"punctuation.definition.string.begin.sql"},2:{name:"punctuation.definition.string.end.sql"}},comment:"this is faster than the next begin/end rule since sub-pattern will match till end-of-line and SQL files tend to have very long lines.",match:'(")[^"#]*(")',name:"string.quoted.double.sql"},{begin:'"',beginCaptures:{0:{name:"punctuation.definition.string.begin.sql"}},end:'"',endCaptures:{0:{name:"punctuation.definition.string.end.sql"}},name:"string.quoted.double.sql",patterns:[{include:"#string_interpolation"}]},{begin:"%\\{",beginCaptures:{0:{name:"punctuation.definition.string.begin.sql"}},end:"\\}",endCaptures:{0:{name:"punctuation.definition.string.end.sql"}},name:"string.other.quoted.brackets.sql",patterns:[{include:"#string_interpolation"}]}]}}};export{e as default};