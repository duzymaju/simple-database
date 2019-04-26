<?php

use PHPUnit\Framework\TestCase;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\MySql\Command;
use SimpleDatabase\Client\MySql\Query;

final class QueryTest extends TestCase
{
    /** @var ConnectionInterface|PHPUnit_Framework_MockObject_MockObject */
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
        $query = new Query($this->connectionMock, Command::TYPE_SELECT, 'Table', 't', [
            'jt.*', 'ljt.param3', 'rjt.param3',
        ]);
        $query
            ->join('JoinedTable', 'jt', 'jt.param1 = 1')
            ->leftJoin('LeftJoinedTable', 'ljt', [ 'jt.param2 = ljt.param1', 'ljt.param2 = "w"' ])
            ->rightJoin('RightJoinedTable', 'rjt', 'jt.param3 = rjt.param1')
            ->outerJoin('OuterJoinedTable', 'ojt', [ 'ljt.param2 = 3.5' ])
            ->where([ 'aa = :aa', 'bb = :bb', 'cc = :cc' ])
            ->groupBy([ 'ljt.param1', 'ljt.param2' ])
            ->orderBy([ 'jt.param2' => 'DESC', 'rjt.param1' => 'ASC' ])
            ->limit(10, 5)
            ->bindParam('aa', Query::PARAM_INT)
            ->bindParam('bb', Query::PARAM_STRING)
            ->bindParam('cc', Query::PARAM_BOOL)
        ;

        $this->assertEquals('SELECT jt.*, ljt.param3, rjt.param3 FROM Table t ' .
            'INNER JOIN JoinedTable jt ON jt.param1 = 1 ' .
            'LEFT OUTER JOIN LeftJoinedTable ljt ON jt.param2 = ljt.param1 && ljt.param2 = "w" ' .
            'RIGHT OUTER JOIN RightJoinedTable rjt ON jt.param3 = rjt.param1 ' .
            'FULL OUTER JOIN OuterJoinedTable ojt ON ljt.param2 = 3.5 ' .
            'WHERE aa = :aa && bb = :bb && cc = :cc ' .
            'ORDER BY jt.param2 DESC, rjt.param1 ASC ' .
            'GROUP BY ljt.param1, ljt.param2 ' .
            'LIMIT 10, 5', $query->getQueryString());
    }

    /** Test delete query */
    public function testDeleteQuery()
    {
        $query = new Query($this->connectionMock, Command::TYPE_DELETE, 'Table', null, [
            'itemOfImproperList',
        ]);
        $query->where([ 'aa = :aa', 'bb = :bb' ]);

        $this->assertEquals('DELETE FROM Table WHERE aa = :aa && bb = :bb', $query->getQueryString());
    }
}
