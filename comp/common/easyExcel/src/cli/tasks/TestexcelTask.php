<?php

/**
 * test
 * 执行此脚本测试 php cli.php testexcel -process generator
 */

use Dcat\EasyExcel\Excel;

class TestexcelTask extends CliApp
{
    public function mainAction(array $params = null)
    {
        if (empty($params['process'])) {
            exit('process 必须');
        }

        $this->{$params['process']}();

        exit('done');
    }

    private function storeXlsx()
    {
        $array = [
            ['id' => 1, 'name' => 'Brakus', 'email' => 'treutel@eg.com', 'created_at' => '...'],
        ];

        $headings = ['id' => 'ID', 'name' => '名称', 'email' => '邮箱'];

        // xlsx
        Excel::export($array)->headings($headings)->store('users.xlsx');
    }

    private function storeCsv()
    {
        $array = [
            ['id' => 1, 'name' => 'Brakus', 'email' => 'treutel@eg.com', 'created_at' => '...'],
        ];

        $headings = ['id' => 'ID', 'name' => '名称', 'email' => '邮箱'];

        // xlsx
        Excel::export($array)->headings($headings)->store('users.csv');
    }

    private function storeOds()
    {
        $array = [
            ['id' => 1, 'name' => 'Brakus', 'email' => 'treutel@eg.com', 'created_at' => '...'],
        ];

        $headings = ['id' => 'ID', 'name' => '名称', 'email' => '邮箱'];

        // xlsx
        Excel::export($array)->headings($headings)->store('users.ods');
    }

    private function read()
    {
        $array = [
            ['id' => 1, 'name' => 'Brakus', 'email' => 'treutel@eg.com', 'created_at' => '...'],
        ];

        // 导出xlsx类型文件
//        $xlsxContents = Excel::export($array)->xlsx()->raw();
//        $xlsxContents = Excel::xlsx($array)->raw();
//        dd($xlsxContents);

        // 导出csv类型文件
        $csvContents = Excel::export($array)->csv()->raw();
//        $csvContents = Excel::csv($array)->raw();
        dd($csvContents);

        // 导出ods类型文件
        $odsContents = Excel::export($array)->ods()->raw();
        $odsContents = Excel::ods($array)->raw();
    }

    private function generator()
    {
        $generatorFactory = function () {
            $array = [
                ['id' => 1, 'name' => 'Brakus', 'email' => 'treutel@eg.com', 'created_at' => '...'],
            ];

            foreach ($array as $value) {
                yield $value;
            }
        };

        $csvContents = Excel::export($generatorFactory())->csv()->raw();
        dd($csvContents);
    }
}
