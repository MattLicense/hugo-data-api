<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 22/03/14
 * Time: 20:03
 */

namespace Hugo\Data\Storage\DB;


class QueryTest extends \PHPUnit_Framework_TestCase {

    protected $store;
    protected $query;

    public function setUp()
    {
        $this->store = $this->getMockBuilder('\\Hugo\\Data\\Storage\\DB\\MySQL')
                            ->disableOriginalConstructor()
                            ->getMock();
        $this->query = new Query($this->store);
    }

    public function testSelectQueries()
    {
        $selectQuery = $this->query->select('table', [])->where('field', 'value');
        $this->assertEquals("SELECT * FROM table WHERE `field` = 'value'", $selectQuery->prepareQuery());
        $this->query->resetQuery();

        $selectQuery = $this->query->select('table', ['id', 'field'])->where('field', '%test%', Query::WHERE_LIKE);
        $this->assertEquals("SELECT `id`, `field` FROM table WHERE `field` LIKE '%test%'", $selectQuery->prepareQuery());
        $this->query->resetQuery();
    }

    public function testInsertQueries()
    {
        $insertQuery = $this->query->insert('table')->set('field','value');
        $this->assertEquals("INSERT INTO table (`field`) VALUES ('value')", $insertQuery->prepareQuery());
        $this->query->resetQuery();

        $insertQuery = $this->query->insert('table')->setArray(['field_1' => 'value_1', 'field_2' => 'value_2']);
        $this->assertEquals("INSERT INTO table (`field_1`,`field_2`) VALUES ('value_1','value_2')", $insertQuery->prepareQuery());
        $this->query->resetQuery();
    }

    public function testUpdateQueries()
    {
        $updateQuery = $this->query->update('table')->setArray(['f1' => 'v1', 'f2' => 'v2', 'f3' => 'v3'])->where('id',2);
        $this->assertEquals("UPDATE table (`f1`,`f2`,`f3`) VALUES ('v1','v2','v3') WHERE `id` = '2'", $updateQuery->prepareQuery());
        $this->query->resetQuery();

        $updateQuery = $this->query->update('table')->set('field_1','value_1')->where('id', 1, Query::WHERE_NTEQU);
        $this->assertEquals("UPDATE table (`field_1`) VALUES ('value_1') WHERE `id` <> '1'", $updateQuery->prepareQuery());
        $this->query->resetQuery();
    }

    public function testDeleteQueries()
    {
        $deleteQuery = $this->query->delete('table')->where('id',1);
        $this->assertEquals("DELETE FROM table WHERE `id` = '1'", $deleteQuery->prepareQuery());
        $this->query->resetQuery();

        $deleteQuery = $this->query->delete('table')->where('field', 2010, Query::WHERE_LT);
        $this->assertEquals("DELETE FROM table WHERE `field` < '2010'", $deleteQuery->prepareQuery());
        $this->query->resetQuery();
    }

    public function testCreateQueries()
    {
        $columns = [
            'f1' => 'VARCHAR(45)',
            'f2' => 'INT',
            'f3' => 'VARCHAR(255)',
            'f4' => 'DATETIME'
        ];
        $createQuery = $this->query->createTable('table')->addColumns($columns);
        $this->assertEquals("CREATE TABLE table (`f1` VARCHAR(45), `f2` INT, `f3` VARCHAR(255), `f4` DATETIME) ENGINE = InnoDB", $createQuery->prepareQuery());
        $this->query->resetQuery();

        $createQuery = $this->query->createTable('table')
                                   ->addColumn('field1', 'VARCHAR(45)')
                                   ->addColumn('field2', 'INT')
                                   ->addConstraint(Query::CONSTRAINT_PK, 'field2');
        $this->assertEquals("CREATE TABLE table (`field1` VARCHAR(45), `field2` INT , PRIMARY KEY (`field2`)) ENGINE = InnoDB",$createQuery->prepareQuery());
        $this->query->resetQuery();
    }

    public function testDropQuery()
    {
        $dropQuery = $this->query->drop('table');
        $this->assertEquals("DROP TABLE table", $dropQuery->prepareQuery());
        $this->query->resetQuery();
    }

    public function testLoadFileQuery()
    {
        $file = $this->getMock('\\SplFileObject', ['getRealPath'], ['php://memory']);
        $file->expects($this->any())
             ->method('getRealPath')
             ->will($this->returnValue('mockTestFile.csv'));

        $loadFileQuery = $this->query->loadDataInFile('table', ['f1', 'f2', 'f3'], $file);
        $expected = "LOAD DATA INFILE 'mockTestFile.csv' INTO TABLE table" .
                    " FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' (`f1`,`f2`,`f3`)";
        $this->assertEquals($expected, $loadFileQuery->prepareQuery());
        $this->query->resetQuery();
    }

}
