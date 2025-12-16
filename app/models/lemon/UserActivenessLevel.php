<?php

namespace Imee\Models\Lemon;

class UserActivenessLevel extends BaseModel
{
    protected static $primaryKey = 'id';

	private static $_levels = array(
		// 46 to 50
		2610940,
		2398440,
		2199140,
		2012540,
		1838040,
		// 41 to 45
		1675140,
		1523240,
		1381940,
		1250640,
		1128940,
		// 36 to 40
		1016340,
		912340,
		816540,
		728440,
		647640,
		// 31 to 35
		573740,
		506340,
		445040,
		389540,
		339340,
		// 26 to 30
		294140,
		253640,
		217440,
		185340,
		156940,
		// 21 to 25
		131940,
		110040,
		91040,
		74640,
		60640,
		// 16 to 20
		48740,
		38740,
		30440,
		23640,
		18140,
		// 11 to 15
		13740,
		10340,
		7640,
		5540,
		3940,
		// 6 to 10
		2740,
		1840,
		1190,
		740,
		440,
		// 1 to 5
		240,
		120,
		60,
		20,
		0,
	);

	public static function getLevel(int $point): int
	{
		$level = $len = count(self::$_levels);
		if ($point < self::$_levels[$len-1]) return 0;
		for ($i = 0; $i < $len; $i++) {
			if ($point >= self::$_levels[$i]) {
				break;
			}
			$level--;
		}
		return $level;
	}

	public static function getLevelPoints(int $level): array
	{
		$data = ['min' => 0, 'max' => 0];
		if ($level <= 0) return $data;

		$_level = self::$_levels;
		sort($_level);

		$count = count($_level);
		if ($level > $count) {
			$data['min'] = $_level[$count - 1];
			return $data;
		}

		$data['min'] = $_level[$level - 1];
		$data['max'] = $_level[$level] - 1;
		return $data;
	}

    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition=[])
    {
        $alias = '';
        if (isset($condition['alias'])) {
            $alias = $condition['alias'] . '.';
        }
        $query = static::baseQueryBuilder($condition);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case "uid_array":
                    $query->inWhere('uid', $value);
                    break;
                case 'columns':
                    // 查询的字段
                    $query->columns($value);
                    break;
                case 'orderBy':
                    $query->orderBy($value);
                    break;
                case 'groupBy':
                    $query->groupBy($value);
                    break;
                case 'limit':
                    $query->limit($value);
                    break;
                case 'offset':
                    $query->offset($value);
                    break;
                default:
                    $query->andWhere($alias.$key." = :{$key}:", ["{$key}" => $value]);
                    break;
            }
        }
        return $query;
    }
}