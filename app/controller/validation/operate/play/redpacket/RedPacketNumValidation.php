<?php

namespace Imee\Controller\Validation\Operate\Play\Redpacket;

use Imee\Comp\Common\Validation\Validator;

class RedPacketNumValidation extends Validator
{
    protected function rules()
    {
        return [
            'big_area'    => 'required|integer',
            'amount1'=> 'required|integer|min:100|max:1000000',
            'amount2'=> 'required|integer|min:100|max:1000000',
            'amount3'=> 'required|integer|min:100|max:1000000',
            'amount4'=> 'required|integer|min:100|max:1000000',
            'num_1_1' => 'required|integer|min:6|max:100',
            'num_1_2' => 'required|integer|min:6|max:100',
            'num_1_3' => 'required|integer|min:6|max:100',
            'num_1_4' => 'required|integer|min:6|max:100',
            'num_2_1' => 'required|integer|min:6|max:100',
            'num_2_2' => 'required|integer|min:6|max:100',
            'num_2_3' => 'required|integer|min:6|max:100',
            'num_2_4' => 'required|integer|min:6|max:100',
            'num_3_1' => 'required|integer|min:6|max:100',
            'num_3_2' => 'required|integer|min:6|max:100',
            'num_3_3' => 'required|integer|min:6|max:100',
            'num_3_4' => 'required|integer|min:6|max:100',
            'num_4_1' => 'required|integer|min:6|max:100',
            'num_4_2' => 'required|integer|min:6|max:100',
            'num_4_3' => 'required|integer|min:6|max:100',
            'num_4_4' => 'required|integer|min:6|max:100',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'big_area' => '运营大区',
            'amount1'=>'红包金额1',
            'amount2'=>'红包金额2',
            'amount3'=>'红包金额3',
            'amount4'=>'红包金额4',
            'num_1_1'=>'档位1下红包个数1',
            'num_1_2'=>'档位1下红包个数2',
            'num_1_3'=>'档位1下红包个数3',
            'num_1_4'=>'档位1下红包个数4',
            'num_2_1'=>'档位2下红包个数1',
            'num_2_2'=>'档位2下红包个数2',
            'num_2_3'=>'档位2下红包个数3',
            'num_2_4'=>'档位2下红包个数4',
            'num_3_1'=>'档位3下红包个数1',
            'num_3_2'=>'档位3下红包个数2',
            'num_3_3'=>'档位3下红包个数3',
            'num_3_4'=>'档位3下红包个数4',
            'num_4_1'=>'档位4下红包个数1',
            'num_4_2'=>'档位4下红包个数2',
            'num_4_3'=>'档位4下红包个数3',
            'num_4_4'=>'档位4下红包个数4',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'amount1.min' => '红包金额1区间为100-1000000',
            'amount1.max' => '红包金额1区间为100-1000000',
            'amount2.min' => '红包金额2区间为100-1000000',
            'amount2.max' => '红包金额2区间为100-1000000',
            'amount3.min' => '红包金额3区间为100-1000000',
            'amount3.max' => '红包金额3区间为100-1000000',
            'amount4.min' => '红包金额4区间为100-1000000',
            'amount4.max' => '红包金额4区间为100-1000000',
            'num_1_1.min' => '档位1下红包个数1区间为6-100',
            'num_1_1.max' => '档位1下红包个数1区间为6-100',
            'num_1_2.min' => '档位1下红包个数2区间为6-100',
            'num_1_2.max' => '档位1下红包个数2区间为6-100',
            'num_1_3.min' => '档位1下红包个数3区间为6-100',
            'num_1_3.max' => '档位1下红包个数3区间为6-100',
            'num_1_4.min' => '档位1下红包个数4区间为6-100',
            'num_1_4.max' => '档位1下红包个数4区间为6-100',
            'num_2_1.min' => '档位2下红包个数1区间为6-100',
            'num_2_1.max' => '档位2下红包个数1区间为6-100',
            'num_2_2.min' => '档位2下红包个数2区间为6-100',
            'num_2_2.max' => '档位2下红包个数2区间为6-100',
            'num_2_3.min' => '档位2下红包个数3区间为6-100',
            'num_2_3.max' => '档位2下红包个数3区间为6-100',
            'num_2_4.min' => '档位2下红包个数4区间为6-100',
            'num_2_4.max' => '档位2下红包个数4区间为6-100',
            'num_3_1.min' => '档位3下红包个数1区间为6-100',
            'num_3_1.max' => '档位3下红包个数1区间为6-100',
            'num_3_2.min' => '档位3下红包个数2区间为6-100',
            'num_3_2.max' => '档位3下红包个数2区间为6-100',
            'num_3_3.min' => '档位3下红包个数3区间为6-100',
            'num_3_3.max' => '档位3下红包个数3区间为6-100',
            'num_3_4.min' => '档位3下红包个数4区间为6-100',
            'num_3_4.max' => '档位3下红包个数4区间为6-100',
            'num_4_1.min' => '档位4下红包个数1区间为6-100',
            'num_4_1.max' => '档位4下红包个数1区间为6-100',
            'num_4_2.min' => '档位4下红包个数2区间为6-100',
            'num_4_2.max' => '档位4下红包个数2区间为6-100',
            'num_4_3.min' => '档位4下红包个数3区间为6-100',
            'num_4_3.max' => '档位4下红包个数3区间为6-100',
            'num_4_4.min' => '档位4下红包个数4区间为6-100',
            'num_4_4.max' => '档位4下红包个数4区间为6-100',
        ];
    }

    /**
     * 返回数据结构
     */
    protected function response()
    {
        return [
            'result' => [
                'success' => true,
                'code' => 0,
                'msg' => '',
                'data' => null,
            ],
        ];
    }
}