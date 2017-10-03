<?php

return [

    'install' => function ($app) {

        $db = $app['db'];
        $util = $db->getUtility();

        if ($util->tableExists('@taxonomy_term') === false) {
            $util->createTable('@taxonomy_term', function ($table) {
                $table->addColumn('id', 'integer', ['unsigned' => true, 'length' => 10, 'autoincrement' => true]);
                $table->addColumn('parent_id', 'integer', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('priority', 'integer', ['default' => 0]);
                $table->addColumn('status', 'smallint');
                $table->addColumn('title', 'string', ['length' => 255]);
                $table->addColumn('slug', 'string', ['length' => 255]);
                $table->addColumn('path', 'string', ['length' => 1023]);
                $table->addColumn('link', 'string', ['length' => 255]);
                $table->addColumn('type', 'string', ['length' => 255]);
                $table->addColumn('taxonomy', 'string', ['length' => 255]);
                $table->addColumn('data', 'json_array', ['notnull' => false]);
                $table->setPrimaryKey(['id']);
            });
        }

        if ($util->tableExists('@taxonomy_term_item') === false) {
            $util->createTable('@taxonomy_term_item', function ($table) {
                $table->addColumn('id', 'integer', ['unsigned' => true, 'length' => 10, 'autoincrement' => true]);
                $table->addColumn('item_id', 'integer', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('term_id', 'integer', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('term_ordering', 'integer', ['default' => 0]);
                $table->setPrimaryKey(['id']);
            });
        }

    },

	'uninstall' => function ($app) {

	},

	'updates' => [
	]

];