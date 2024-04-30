<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\MySql\Command;
use SimpleDatabase\Client\MySql\Query;

final class QueryTest extends TestCase
{
    /** @var ConnectionInterface|MockObject */
    private $connectionMock;

    /** @before */
    public function setupMocks()
    {
        $this->connectionMock = $this->createMock(ConnectionInterface::class);
        $this->connectionMock
            ->method('getClient')
            ->willReturn(null)
        ;
    }

    /** Test select query */
    public function testSelectQuery()
    {
        $query = new Query($this->connectionMock, Command::TYPE_SELECT, 'test_table', 't', [
            'jt.*', 'ljt.param3', 'rjt.param3',
        ]);
        $query
            ->addToSelect(['jt.param1'])
            ->join('JoinedTable', 'jt', 'jt.param1 = 1')
            ->leftJoin('LeftJoinedTable', 'ljt', ['jt.param2 = ljt.param1', 'ljt.param2 = "w"'])
            ->rightJoin('RightJoinedTable', 'rjt', 'jt.param3 = rjt.param1')
            ->outerJoin('OuterJoinedTable', 'ojt', ['ljt.param2 = 3.5'])
            ->where(['aa = :aa', 'bb = :bb', 'cc = :cc'])
            ->groupBy(['ljt.param1', 'ljt.param2'], 'ljt.param1 != ljt.param2')
            ->orderBy(['jt.param2' => 'desc', 'rjt.param1' => 'ASC', 'jt.param3 DESC', 'RAND()'])
            ->limit(10, 5)
            ->bindParam('aa', Query::PARAM_INT)
            ->bindParam('bb', Query::PARAM_STRING)
            ->bindParam('cc', Query::PARAM_BOOL)
        ;

        $this->assertEquals(
            'SELECT jt.*, ljt.param3, rjt.param3, jt.param1 FROM test_table t' .
            ' INNER JOIN JoinedTable jt ON jt.param1 = 1' .
            ' LEFT OUTER JOIN LeftJoinedTable ljt ON jt.param2 = ljt.param1 && ljt.param2 = "w"' .
            ' RIGHT OUTER JOIN RightJoinedTable rjt ON jt.param3 = rjt.param1' .
            ' FULL OUTER JOIN OuterJoinedTable ojt ON ljt.param2 = 3.5' .
            ' WHERE (aa = :aa && bb = :bb && cc = :cc)' .
            ' GROUP BY ljt.param1, ljt.param2' .
            ' HAVING ljt.param1 != ljt.param2' .
            ' ORDER BY jt.param2 DESC, rjt.param1 ASC, jt.param3 DESC, RAND()' .
            ' LIMIT 5, 10',
            $query->toString()
        );
    }

    /** Test cloned select query */
    public function testClonedSelectQuery()
    {
        $query = new Query($this->connectionMock, Command::TYPE_SELECT, 'test_table', 't', [
            'jt.*', 'ljt.param3', 'rjt.param3',
        ]);
        $query
            ->join('JoinedTable', 'jt', 'jt.param1 = 1')
            ->leftJoin('LeftJoinedTable', 'ljt', ['jt.param2 = ljt.param1', 'ljt.param2 = "w"'])
            ->rightJoin('RightJoinedTable', 'rjt', 'jt.param3 = rjt.param1')
            ->outerJoin('OuterJoinedTable', 'ojt', ['ljt.param2 = 3.5'])
            ->where($query->allOf(['aa = :aa', $query->anyOf(['bb = :bb', 'cc = :cc'])]))
            ->groupBy(['ljt.param1', 'ljt.param2'], 'ljt.param1 != ljt.param2')
            ->orderBy(['jt.param2' => 'desc', 'rjt.param1' => 'ASC', 'jt.param3 DESC', 'RAND()'])
            ->limit(10, 5)
            ->bindParam('aa', Query::PARAM_INT)
            ->bindParam('bb', Query::PARAM_STRING)
            ->bindParam('cc', Query::PARAM_BOOL)
        ;
        $clonedQuery = $query
            ->cloneSelect('ljt.*')
            ->addToSelect([])
            ->addToSelect('jt.param1')
            ->addToSelect(['jt.param2', 'jt.param3'])
        ;

        $this->assertEquals(
            'SELECT ljt.*, jt.param1, jt.param2, jt.param3 FROM test_table t' .
            ' INNER JOIN JoinedTable jt ON jt.param1 = 1' .
            ' LEFT OUTER JOIN LeftJoinedTable ljt ON jt.param2 = ljt.param1 && ljt.param2 = "w"' .
            ' RIGHT OUTER JOIN RightJoinedTable rjt ON jt.param3 = rjt.param1' .
            ' FULL OUTER JOIN OuterJoinedTable ojt ON ljt.param2 = 3.5' .
            ' WHERE (aa = :aa && (bb = :bb || cc = :cc))' .
            ' GROUP BY ljt.param1, ljt.param2' .
            ' HAVING ljt.param1 != ljt.param2' .
            ' ORDER BY jt.param2 DESC, rjt.param1 ASC, jt.param3 DESC, RAND()' .
            ' LIMIT 5, 10',
            $clonedQuery->toString()
        );
    }

    /** Test insert query */
    public function testInsertQuery()
    {
        $query = new Query($this->connectionMock, Command::TYPE_INSERT, 'test_table');
        $query
            ->set(['param1 = :param1', 'param2 = :param2', 'param3 = :param3'])
            ->bindParam('param1', Query::PARAM_INT)
            ->bindParam('param2', Query::PARAM_STRING)
            ->bindParam('param3', Query::PARAM_FLOAT)
        ;

        $this->assertEquals(
            'INSERT INTO test_table SET param1 = :param1, param2 = :param2, param3 = :param3',
            $query->toString()
        );
    }

    /** Test update query */
    public function testUpdateQuery()
    {
        $query = new Query($this->connectionMock, Command::TYPE_UPDATE, 'test_table', 't', []);
        $query
            ->set(['param2 = :param2'])
            ->where('param1 = :param1')
            ->bindParam('param1', Query::PARAM_INT)
            ->bindParam('param2', Query::PARAM_STRING)
        ;

        $this->assertEquals('UPDATE test_table t SET param2 = :param2 WHERE (param1 = :param1)', $query->toString());
    }

    /** Test delete query */
    public function testDeleteQuery()
    {
        $query = new Query($this->connectionMock, Command::TYPE_DELETE, 'test_table', null, [
            'itemOfImproperList',
        ]);
        $query->where(['aa = :aa', 'bb = :bb']);

        $this->assertEquals('DELETE FROM test_table WHERE (aa = :aa && bb = :bb)', $query->toString());
    }
}
