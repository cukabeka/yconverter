<?php

/**
 * This file is part of the YConverter package.
 *
 * @author (c) Yakamara Media GmbH & Co. KG
 * @author Thomas Blum <thomas.blum@yakamara.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace YConverter;

class Converter
{
    public static $tablePrefix = 'yconverter_';
    public static $phpValueField = 19;
    public static $htmlValueField = 20;
    public $tables = [];
    public $regex = [];
    public $messages = [];
    public $tableStructure = [];

    public function __construct()
    {
        global $REX;

        $this->rex = $REX;

        $this->db = \rex_sql::factory();
        $this->db->debugsql = 0;


        $this->matches = [
            [
                // $REX
                'regex' => '(\$REX\s*\[[\'\"]$$SEARCH$$[\'\"]\])',
                'matches' => [
                    //'MOD_REWRITE',
                    '.*?',
                ]
            ], [
                'matches' => [
                    'rex_addslashes',
                    'rex_call_func',
                    'rex_check_callable',
                    'rex_create_lang',
                    'rex_getAttributes',
                    'rex_lang_is_utf8',
                    'rex_replace_dynamic_contents',
                    'rex_setAttributes',
                    'rex_tabindex',
                ]
            ],
        ];
        $this->replaces = [
            [
                // $REX
                'regex' => '\$REX\s*\[[\'\"]$$SEARCH$$[\'\"]\][^\[]',
                'replaces' => [
                    ['ARTICLE_ID' => 'rex_article::getCurrentId()'],
                    ['CLANG' => 'rex_clang::getAll()'],
                    ['CUR_CLANG' => 'rex_clang::getCurrentId()'],
                    ['ERROR_EMAIL' => 'rex::getErrorEmail()'],
                    ['FRONTEND_FILE' => 'rex_path::frontendController()'],
                    ['FRONTEND_PATH' => 'rex_path::frontend()'],
                    ['HTDOCS_PATH' => 'rex_path::frontend()'],
                    ['INCLUDE_PATH' => 'rex_path::src() . \'/addons/\''],
                    ['MEDIAFOLDER' => 'rex_path::media()'],
                    ['NOTFOUND_ARTICLE_ID' => 'rex_article::getNotFoundArticleId()'],
                    ['REDAXO' => 'rex::isBackend()'],
                    ['SERVER' => 'rex::getServer()'],
                    ['SERVERNAME' => 'rex::getServerName()'],
                    ['START_ARTICLE_ID' => 'rex_article::getSiteStartArticleId()'],
                    ['TABLE_PREFIX' => 'rex::getTablePrefix()'],
                    ['USER' => 'rex::getUser()'],
                ]
            ], [
                // OOF Spezial
                'replaces' => [
                    ['new\s*rex_article' => 'new rex_article_content'],
                    ['OOArticle\s*::\s*getArticleById\(' => 'rex_article::get('],
                    ['OOCategory\s*::\s*getCategoryById\(' => 'rex_category::get('],
                    ['OOMedia\s*::\s*getMediaByFilename\(' => 'rex_media::get('],
                    ['OOMediaCategory\s*::\s*getCategoryById\(' => 'rex_media_category::get('],
                    ['OOAddon\s*::\s*isActivated\((.*?)\)' => 'rex_addon::get($1)->isActivated()'],
                    ['OOAddon\s*::\s*isAvailable\((.*?)\)' => 'rex_addon::get($1)->isAvailable()'],
                    ['OOAddon\s*::\s*isInstalled\((.*?)\)' => 'rex_addon::get($1)->isInstalled()'],
                    ['OOAddon\s*::\s*getProperty\((.*?),\s*(.*?)\)' => 'rex_addon::get($1)->getProperty($2)'],
                    ['OOPlugin\s*::\s*isActivated\((.*?),\s*(.*?)\)' => 'rex_plugin::get($1, $2)->isActivated()'],
                    ['OOPlugin\s*::\s*isAvailable\((.*?),\s*(.*?)\)' => 'rex_plugin::get($1, $2)->isAvailable()'],
                    ['OOPlugin\s*::\s*isInstalled\((.*?),\s*(.*?)\)' => 'rex_plugin::get($1, $2)->isInstalled()'],
                    ['OOPlugin\s*::\s*getProperty\((.*?),\s*(.*?),\s*(.*?)\)' => 'rex_plugin::get($1, $2)->getProperty($3)'],

                ]
            ], [
                // OOF
                'regex' => '$$SEARCH$$\s*::\s*\((.*?)\)',
                'replaces' => [
                    ['OOArticle' => 'rex_article::($1)'],
                    ['OOCategory' => 'rex_category::($1)'],
                    ['OOMedia' => 'rex_media::($1)'],
                    ['OOMediaCategory' => 'rex_media_category::($1)'],
                    ['OOArticleSlice' => 'rex_article_slice::($1)'],
                ]
            ], [
                // OO isValid
                'regex' => '$$SEARCH$$\s*::\s*isValid\((.*?)\)',
                'replaces' => [
                    ['OOArticle' => '$1 instanceof rex_article'],
                    ['OOCategory' => '$1 instanceof rex_category'],
                    ['OOMedia' => '$1 instanceof rex_media'],
                    ['OOMediaCategory' => '$1 instanceof rex_media_category'],
                ]
            ], [
                // REX_
                'replaces' => [
                    ['REX_EXTENSION_EARLY' => 'rex_extension::EARLY'],
                    ['REX_EXTENSION_LATE' => 'rex_extension::LATE'],
                    ['REX_FILE\[([1-9]+)\]' => 'REX_MEDIA[id=$1]'],
                    ['REX_HTML_VALUE\[([0-9]+)\]' => 'REX_VALUE[id=$1 output=html]'],
                    ['REX_HTML_VALUE\[id=[\"\']([0-9]+)(.*?)\]' => 'REX_VALUE[id=$1 output=html $2]'],
                    ['REX_IS_VALUE\[([1-9]+)\]' => 'REX_VALUE[id=$1 isset=1]'],
                    ['REX_LINK_BUTTON\[(id=)?([1-9]|10)\]' => 'REX_LINK[id=$2 widget=1]'],
                    ['REX_LINKLIST_BUTTON\[(id=)?([1-9]|10)\]' => 'REX_LINKLIST[id=$2 widget=1]'],
                    ['REX_MEDIA_BUTTON\[(id=)?([1-9]|10)\]' => 'REX_MEDIA[id=$2 widget=1]'],
                    ['REX_MEDIALIST_BUTTON\[(id=)?([1-9]|10)\]' => 'REX_MEDIALIST[id=$2 widget=1]'],
                    // muss hier stehen
                    ['INPUT_PHP' => 'REX_INPUT_VALUE[' . self::$phpValueField . ']'],
                    ['REX_PHP' => 'REX_VALUE[id=' . self::$phpValueField . ' output=php]'],
                    ['INPUT_HTML' => 'REX_INPUT_VALUE[' . self::$htmlValueField . ']'],
                    ['REX_HTML' => 'REX_VALUE[id=' . self::$htmlValueField . ' output=html]'],
                    ['([^_])VALUE\[(id=)?([1-9]+[0-9]*)\]' => '$1REX_INPUT_VALUE[id=$3]'],
                ]
            ], [
                // Extension Points
                'replaces' => [
                    ['ALL_GENERATED' => 'CACHE_DELETED'],
                    ['ADDONS_INCLUDED' => 'PACKAGES_INCLUDED'],
                    ['OUTPUT_FILTER_CACHE' => 'RESPONSE_SHUTDOWN'],
                    ['OOMEDIA_IS_IN_USE' => 'MEDIA_IS_IN_USE'],
                ]
            ], [
                // Rest
                'replaces' => [
                    ['\$I18N\-\>msg\(' => 'rex_i18n::msg('],
                    ['(\/?)files\/' => '$1media/'],
                    ['getDescription\(' => 'getValue(\'description\''],
                    ['isStartPage\(' => 'isStartArticle('],
                    ['rex_absPath\(' => 'rex_path::absolute('],
                    ['rex_copyDir\(' => 'rex_dir::copy('],
                    ['rex_createDir\(' => 'rex_dir::create('],
                    ['rex_deleteAll\(' => 'rex_deleteCache('],
                    ['rex_deleteDir\(' => 'rex_dir::delete('],
                    ['rex_deleteFiles\(' => 'rex_dir::deleteFiles('],
                    ['rex_generateAll\(' => 'rex_deleteCache('],
                    ['rex_hasBackendSession\(' => 'rex_backend_login::hasSession('],
                    ['rex_highlight_string\(' => 'rex_string::highlight('],
                    ['rex_highlight_file\(' => 'rex_string::highlight('],
                    ['rex_info\(' => 'rex_view::info('],
                    ['rex_install_dump\(' => 'rex_sql_util::importDump('],
                    ['rex_organize_priorities\(' => 'rex_sql_util::organizePriorities('],
                    ['rex_register_extension\(' => 'rex_extension::register('],
                    ['rex_register_extension_point\(' => 'rex_extension::registerPoint('],
                    ['rex_send_article\(' => 'rex_response::sendArticle('],
                    ['rex_send_content\(' => 'rex_response::sendContent('],
                    ['rex_send_file\(' => 'rex_response::sendFile('],
                    ['rex_send_resource\(' => 'rex_response::sendResource('],
                    ['rex_split_string' => 'rex_string::split()'],
                    ['rex_title\(' => 'rex_view::title('],
                    ['rex_translate\(' => 'rex_i18n::translate('],
                    ['rex_warning\(' => 'rex_view::error('],


                ]
            ],

        ];

        $this->tables = [
            // Metainfo
            // - - - - - - - - - - - - - - - - - -
            '62_params' => [
                'r5Table' => 'metainfo_field',
                'addColumns' => [
                    ['callback' => 'text AFTER validate'],
                ],
                'changeColumns' => [
                    ['field_id' => 'id'],
                    ['prior' => 'priority'],
                    ['type' => 'type_id'],
                ],
                'convertTimestamp' => [
                    'createdate', 'updatedate',
                ]
            ],

            '62_type' => [
                'r5Table' => 'metainfo_type',
            ],

            // Image Manager
            // - - - - - - - - - - - - - - - - - -
            '679_types' => [
                'r5Table' => 'media_manager_type',
            ],

            '679_type_effects' => [
                'r5Table' => 'media_manager_type_effect',
                'changeColumns' => [
                    ['prior' => 'priority'],
                ],
                'convertSerialize' => [
                    'parameters',
                ],
                'convertTimestamp' => [
                    'createdate', 'updatedate',
                ]
            ],

            // Action
            // - - - - - - - - - - - - - - - - - -
            'action' => [
                'r5Table' => 'action',
                'convertTimestamp' => [
                    'createdate', 'updatedate',
                ],
                'fireReplaces' => [
                    'preview', 'presave', 'postsave',
                ],
            ],

            // Articles
            // - - - - - - - - - - - - - - - - - -
            'article' => [
                'r5Table' => 'article',
                'changeColumns' => [
                    ['re_id' => 'parent_id'],
                    ['catprior' => 'catpriority'],
                    ['startpage' => 'startarticle'],
                    ['prior' => 'priority'],
                    ['clang' => 'clang_id'],
                ],
                'convertTimestamp' => [
                    'createdate', 'updatedate',
                ],
                'dropColumns' => [
                    'attributes',
                ],
                'callback' => 'YConverter\Converter::callbackModifyArticles'
            ],

            // Article Slices
            // - - - - - - - - - - - - - - - - - -
            'article_slice' => [
                'r5Table' => 'article_slice',
                'changeColumns' => [
                    ['clang' => 'clang_id'],
                    ['ctype' => 'ctype_id'],
                    ['re_article_slice_id' => 'priority'],
                    ['file1' => 'media1'],
                    ['file2' => 'media2'],
                    ['file3' => 'media3'],
                    ['file4' => 'media4'],
                    ['file5' => 'media5'],
                    ['file6' => 'media6'],
                    ['file7' => 'media7'],
                    ['file8' => 'media8'],
                    ['file9' => 'media9'],
                    ['file10' => 'media10'],
                    ['filelist1' => 'medialist1'],
                    ['filelist2' => 'medialist2'],
                    ['filelist3' => 'medialist3'],
                    ['filelist4' => 'medialist4'],
                    ['filelist5' => 'medialist5'],
                    ['filelist6' => 'medialist6'],
                    ['filelist7' => 'medialist7'],
                    ['filelist8' => 'medialist8'],
                    ['filelist9' => 'medialist9'],
                    ['filelist10' => 'medialist10'],
                    ['modultyp_id' => 'module_id'],
                ],
                'fireReplaces' => [
                    'value1', 'value2', 'value3', 'value4', 'value5', 'value6', 'value7', 'value8', 'value9', 'value10',
                    'value11', 'value12', 'value13', 'value14', 'value15', 'value16', 'value17', 'value18', 'value19', 'value20',
                ],
                'moveContents' => [
                    ['php' => 'value' . self::$phpValueField],
                    ['html' => 'value' . self::$htmlValueField],
                ],
                'convertTimestamp' => [
                    'createdate', 'updatedate',
                ],
                'dropColumns' => [
                    'next_article_slice_id', 'php', 'html'
                ],
                'callback' => 'YConverter\Converter::callbackModifyArticleSlices'
            ],

            // Clang
            // - - - - - - - - - - - - - - - - - -
            'clang' => [
                'r5Table' => 'clang',
                'addColumns' => [
                    ['code' => 'varchar(255) AFTER id'],
                    ['priority' => 'int(10) AFTER name'],
                    ['status' => 'tinyint(1) AFTER revision'],
                ],
                'callback' => 'YConverter\Converter::callbackModifyLanguages'
            ],

            // Media
            // - - - - - - - - - - - - - - - - - -
            'file' => [
                'r5Table' => 'media',
                'dropColumns' => [
                    're_file_id',
                ],
                'convertTimestamp' => [
                    'createdate', 'updatedate',
                ]
            ],

            'file_category' => [
                'r5Table' => 'media_category',
                'changeColumns' => [
                    ['re_id' => 'parent_id'],
                ],
                'convertTimestamp' => [
                    'createdate', 'updatedate',
                ]
            ],

            // Module
            // - - - - - - - - - - - - - - - - - -
            'module' => [
                'r5Table' => 'module',
                'changeColumns' => [
                    ['ausgabe' => 'output'],
                    ['eingabe' => 'input'],
                ],
                'convertTimestamp' => [
                    'createdate', 'updatedate',
                ],
                'fireReplaces' => [
                    'input', 'output',
                ],
                'dropColumns' => [
                    'category_id',
                ],
            ],
            'module_action' => [
                'r5Table' => 'module_action',
            ],

            // Templates
            // - - - - - - - - - - - - - - - - - -
            'template' => [
                'r5Table' => 'template',
                'convertSerialize' => [
                    'attributes',
                ],
                'fireReplaces' => [
                    'content',
                ],
                'dropColumns' => [
                    'label',
                ],
            ],

        ];
    }

    public function run()
    {
        $this->createDestinationTables();
        $this->loadDestinationTableStructure();
        $this->modifyDestinationTables();
        $this->callCallbacks();
    }

    public function getR4Table($table)
    {
        global $REX;
        return $REX['TABLE_PREFIX'] . $table;
    }

    public function getR5Table($table)
    {
        return self::$tablePrefix . $this->getR4Table($table);
    }

    public function getTables()
    {
        return array_keys($this->tables);
    }

    public function getTablePrefix()
    {
        return self::$tablePrefix;
    }

    protected function addMessage($string)
    {
        $this->messages[] = rex_info($string);
    }

    protected function addErrorMessage($string)
    {
        $this->messages[] = rex_warning($string);
    }

    public function getMessages()
    {
        return $this->messages;
    }

    protected function createDestinationTables()
    {
        foreach ($this->tables as $r4Table => $params) {
            $r4Table = $this->getR4Table($r4Table);
            $r5Table = $this->getR5Table($params['r5Table']);

            // R5 Tabelle löschen
            $this->db->setQuery('DROP TABLE IF EXISTS `' . $r5Table . '`;');

            // R5 Tabelle erstellen, inkl. der Struktur der R4 Tabelle
            $this->db->setQuery('CREATE TABLE `' . $r5Table . '` LIKE `' . $r4Table . '`;');

            // Daten in R5 Tablle kopieren
            $this->db->setQuery('INSERT ' . $r5Table . ' SELECT * FROM `' . $r4Table . '`;');
        }
        $this->addMessage('Tabellen angelegt und Daten kopiert.');
    }


    protected function loadDestinationTableStructure()
    {
        foreach ($this->tables as $r4Table => $params) {
            $r5Table = $this->getR5Table($params['r5Table']);

            $columns = $this->db->getArray('SHOW COLUMNS FROM `' . $r5Table . '`;');
            foreach ($columns as $column) {
                $this->tableStructure[$r5Table][$column['Field']] = $column;
            }
        }
    }

    protected function getTableColumnType($table, $column)
    {
        return $this->tableStructure[$table][$column]['Type'];
    }

    protected function getFieldsForMessage($fields)
    {
        $return = [];
        foreach ($fields as $field) {
            if (is_array($field)) {
                $return = array_merge($return, array_keys($field));
            } else {
                $return[] = $field;
            }
        }
        return $return;
    }

    protected function modifyDestinationTables()
    {
        foreach ($this->tables as $r4Table => $params) {
            $r5Table = $this->getR5Table($params['r5Table']);

            $messages = [];
            if (isset($params['addColumns'])) {
                $this->addTableColumns($r5Table, $params['addColumns']);
                $messages[] = 'Angelegte Felder:<br /><b style="color: #000; font-weight: 400;">' . implode(', ', $this->getFieldsForMessage($params['addColumns'])) . '</b>';
            }
            if (isset($params['changeColumns'])) {
                $this->changeTableColumns($r5Table, $params['changeColumns']);
                $messages[] = 'Angepasste Felder:<br /><b style="color: #000; font-weight: 400;">' . implode(', ', $this->getFieldsForMessage($params['changeColumns'])) . '</b>';
            }
            if (isset($params['moveContents'])) {
                $this->moveTableContents($r5Table, $params['moveContents']);
                $messages[] = 'Inhalte übertragen:<br /><b style="color: #000; font-weight: 400;">' . implode(', ', $this->getFieldsForMessage($params['moveContents'])) . '</b>';
            }
            if (isset($params['convertSerialize'])) {
                $this->convertTableContents('serializeToJson', $r5Table, $params['convertSerialize']);
                $messages[] = 'Serialisierte Daten konvertiert:<br /><b style="color: #000; font-weight: 400;">' . implode(', ', $this->getFieldsForMessage($params['convertSerialize'])) . '</b>';
            }
            if (isset($params['convertTimestamp'])) {
                $this->convertTableContents('timestampToDatetime', $r5Table, $params['convertTimestamp']);
                $messages[] = 'Timestamps konvertiert:<br /><b style="color: #000; font-weight: 400;">' . implode(', ', $this->getFieldsForMessage($params['convertTimestamp'])) . '</b>';
            }
            if (isset($params['fireReplaces'])) {
                $this->convertTableContents('fireReplaces', $r5Table, $params['fireReplaces']);
                $messages[] = 'Inhalte konvertiert:<br /><b style="color: #000; font-weight: 400;">' . implode(', ', $this->getFieldsForMessage($params['fireReplaces'])) . '</b>';
                $this->checkMatches($r5Table, $params['fireReplaces']);
            }
            if (isset($params['dropColumns'])) {
                $this->dropTableColumns($r5Table, $params['dropColumns']);
                $messages[] = 'Gelöschte Felder:<br /><b style="color: #000; font-weight: 400;">' . implode(', ', $this->getFieldsForMessage($params['dropColumns'])) . '</b>';
            }
            $this->addMessage('Tabelle:<br /><b style="color: #000;">' . $r5Table . '</b><br /><br />' . implode('<br /><br />', $messages));
        }
    }

    protected function callCallbacks()
    {
        foreach ($this->tables as $r4Table => $params) {
            $r5Table = $this->getR5Table($params['r5Table']);

            if (isset($params['callback']) && is_callable($params['callback'])) {
                call_user_func($params['callback'], $params);
                $this->addMessage('Callback für ' . $r5Table . ' aufgerufen');
            }
        }
    }

    protected function addTableColumns($table, array $columns)
    {
        foreach ($columns as $column) {
            foreach ($column as $name => $type) {
                $this->db->setQuery('ALTER TABLE `' . $table .'` ADD COLUMN `' . $name . '` ' . $type);
            }
        }
    }

    protected function changeTableColumns($table, array $columns)
    {
        foreach ($columns as $column) {
            foreach ($column as $oldName => $newName) {
                $type = '';
                if (strpos($newName, ' ') === false) {
                    $type = $this->getTableColumnType($table, $oldName);
                }
                $this->db->setQuery('ALTER TABLE `' . $table .'` CHANGE COLUMN `' . $oldName . '` ' . $newName . ' ' . $type);
            }
        }
    }

    protected function dropTableColumns($table, array $columns)
    {
        foreach ($columns as $column) {
            $this->db->setQuery('ALTER TABLE `' . $table . '` DROP COLUMN `' . $column . '`;');
        }
    }

    protected function moveTableContents($table, array $columns)
    {
        foreach ($columns as $column) {
            foreach ($column as $from => $to) {
                $this->db->setQuery('UPDATE `' . $table . '` SET `' . $to . '` = IF(`' . $from . '` = "", `' . $to . '`, CONCAT(`' . $to . '`, "\n\n\n", `' . $from . '`))');
            }
        }
    }

    protected function convertTableContents($function, $table, array $columns)
    {
        switch ($function) {
            case 'fireReplaces':
                foreach ($columns as $column) {
                    $items = $this->db->getArray('SELECT `id`, `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` != ""');
                    if (count($items)) {
                        foreach ($items as $item) {
                            $this->db->setQuery('UPDATE `' . $table . '` SET `' . $column . '` = \'' .  $this->db->escape($this->fireReplaces($item[$column])) . '\' WHERE `id` = "' . $item['id'] . '"');
                        }
                    }
                }
                break;
            case 'serializeToJson':
                foreach ($columns as $column) {
                    $items = $this->db->getArray('SELECT `id`, `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` != ""');
                    if (count($items)) {
                        foreach ($items as $item) {
                            $this->db->setQuery('UPDATE `' . $table . '` SET `' . $column . '` = \'' . json_encode(unserialize($item[$column])) . '\' WHERE `id` = "' . $item['id'] . '"');
                        }
                    }
                }
                break;
            case 'timestampToDatetime':
                foreach ($columns as $column) {
                    $this->db->setQuery('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $column . '` `' . $column . '` varchar(20)');
                    $this->db->setQuery('UPDATE `' . $table . '` SET `' . $column . '` = IF(`' . $column . '` > 0, FROM_UNIXTIME(`' . $column . '`, "%Y-%m-%d %H:%i:%s"), NOW())');
                    $this->db->setQuery('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $column . '` `' . $column . '` datetime');
                }
                break;
        }
    }

    protected function checkMatches($table, array $columns)
    {
        foreach ($columns as $column) {
            $items = $this->db->getArray('SELECT `id`, `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` != ""');
            if (count($items)) {
                foreach ($items as $item) {

                    foreach ($this->matches as $m) {
                        $search = '';
                        if (isset($m['regex'])) {
                            $search = $m['regex'];
                        }

                        foreach ($m['matches'] as $match) {
                            $expr = $match;
                            if ($search != '') {
                                $expr = str_replace('$$SEARCH$$', $match, $search);
                            }
                            if (preg_match('@' . $expr . '@i', $item[$column])) {
                                preg_match_all('@' . $expr . '@i', $item[$column], $matches);
                                $matches = array_count_values($matches[0]);
                                foreach ($matches as $match => $count) {
                                    $this->addErrorMessage('
                                        <span style="font-weight: 400;"><code>' . $match . '</code> sollte angepasst bzw. nicht mehr verwendet werden.<br /><br />
                                            Tabelle: <b style="color: #000;">' . $table . '</b>' . str_repeat('&nbsp;', 10) . '
                                            Id: <b style="color: #000;">' . $item['id'] . '</b>' . str_repeat('&nbsp;', 10) . '
                                            Spalte: <b style="color: #000;">' . $column . '</b>' . str_repeat('&nbsp;', 10) . '
                                            Vorkommen: <b style="color: #000;">' . $count . '</b>
                                        </span>');
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public function fireReplaces($content)
    {
        foreach ($this->replaces as $r) {
            $search = '';
            if (isset($r['regex'])) {
                $search = $r['regex'];
            }

            foreach ($r['replaces'] as $pair) {
                foreach ($pair as $expr => $replace) {
                    if ($search != '') {
                        $expr = str_replace('$$SEARCH$$', $expr, $search);
                    }
                    $content = preg_replace('@' . $expr . '@i', $replace, $content);
                }
            }
        }
        return $content;
    }


    public static function callbackModifyArticles($params)
    {
        // rex_article anpassen
        $converter = new self();
        $r5Table = $converter->getR5Table($params['r5Table']);
        $converter->db->setQuery('UPDATE `' . $r5Table . '` SET `clang_id` = clang_id +1 ORDER BY clang_id DESC');
    }


    public static function callbackModifyArticleSlices($params)
    {
        // Sprachen anpassen
        $converter = new self();
        $r5Table = $converter->getR5Table($params['r5Table']);
        $converter->db->setQuery('UPDATE `' . $r5Table . '` SET `clang_id` = clang_id +1 ORDER BY clang_id DESC');

        // Prioritäten setzen
        $clangs = $converter->db->getArray('SELECT `id` FROM ' . $converter->getR5Table('clang'));
        foreach ($clangs as $clang) {
            $clang_id = $clang['id'];
            $articles = $converter->db->getArray('SELECT `id` FROM ' . $converter->getR5Table('article') . ' WHERE `clang_id` = "' . $clang_id . '"');

            if ($converter->db->getRows() >= 1) {
                foreach ($articles as $article) {
                    $article_id = $article['id'];
                    $article_clang_id = $clang_id - 1;
                    $slices = yconverterGetSortedSlices($article_id, $article_clang_id);

                    if (count($slices)) {
                        $priority = 0;
                        foreach ($slices as $slice) {
                            $slice_id = $slice->getId();
                            ++$priority;
                            $converter->db->setQuery('UPDATE `' . $r5Table . '` SET `priority` = "' . $priority . '" WHERE `id` = "' . $slice_id . '"');
                        }
                    }
                }
            }
        }
    }

    public static function callbackModifyLanguages($params)
    {
        // rex_clang anpassen
        $converter = new self();
        $r5Table = $converter->getR5Table($params['r5Table']);
        $converter->db->setQuery('UPDATE `' . $r5Table . '` SET `id` = id +1 ORDER BY id DESC');
        $converter->db->setQuery('UPDATE `' . $r5Table . '` SET `priority` = `id`');
        $converter->db->setQuery('UPDATE `' . $r5Table . '` SET `status` = 1');
    }



    protected function pr($array, $exit = false)
    {
        echo '<pre>'; print_r($array); echo '</pre>';
        if ($exit) {
            exit();
        }
    }

}