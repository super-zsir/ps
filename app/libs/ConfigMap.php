<?php

namespace Imee\Libs;


class ConfigMap
{
    private static $_mapUserSex = array(
        1 => "男",
        2 => "女",
    );

    private static $_mapUserEdu = array(
        1 => '博士',
        2 => '硕士',
        3 => '本科',
        4 => '大专',
        5 => '高中',
        6 => '中专',
        7 => '初中',
    );

    private static $_mapUserGrade = array(
        1  => '大一',
        2  => '大二',
        3  => '大三',
        4  => '大四',
        5  => '大五',
        6  => '研一',
        7  => '研二',
        8  => '研三',
        9  => '博一',
        10 => '博二',
        11 => '博三',
        12 => '博四'
    );

    private static $_mapUserPurpose = array(
        1 => '认识新朋友',
        2 => '脱单',
        3 => '和朋友一起玩',
    );

    private static $_mapUserEmotionState = array(
        1 => '单身汪',
        2 => '保留交友空间',
        3 => '恋爱ing',
        4 => '心有所属'
    );

    private static $_mapUserJob = array(
        101   => "信息技术",
        102   => "金融保险",
        103   => "商业服务",
        104   => "工程制造",
        105   => "交通运输",
        106   => "文化传媒",
        107   => "娱乐体育",
        108   => "公共事业",
        109   => "企业职员",
        110   => "学生",
        111   => "自由职业",
        10101 => "IT",
        10102 => "互联网",
        10103 => "通讯",
        10104 => "计算机",
        10105 => "网络游戏",
        10106 => "电信运营",
        10107 => "产品经理",
        10108 => "程序员",
        10109 => "设计师",
        10110 => "网络运营",
        10111 => "网络编辑",
        10112 => "游戏策划",
        10201 => "金融",
        10202 => "投资",
        10203 => "银行",
        10204 => "证券",
        10205 => "保险",
        10206 => "信托/担保",
        10207 => "投资经理",
        10208 => "基金经理",
        10209 => "理财顾问",
        10210 => "客户经理",
        10301 => "咨询",
        10302 => "培训",
        10303 => "个体经营",
        10304 => "旅游",
        10305 => "酒店餐饮",
        10306 => "休闲娱乐",
        10307 => "贸易",
        10308 => "汽车",
        10309 => "房地产",
        10310 => "物业管理",
        10311 => "装修/装潢",
        10312 => "美容美发",
        10401 => "建筑",
        10402 => "土木工程",
        10403 => "机械制造",
        10404 => "电子",
        10405 => "生物医药",
        10406 => "食品",
        10407 => "服装",
        10408 => "能源",
        10501 => "航空",
        10502 => "铁路",
        10503 => "航运/船舶",
        10504 => "公共交通",
        10505 => "物流运输",
        10506 => "空乘",
        10507 => "空姐",
        10601 => "媒体出版",
        10602 => "文化传媒",
        10603 => "广告创意",
        10604 => "公关/会展",
        10605 => "动漫",
        10606 => "电视",
        10607 => "记者",
        10608 => "主持人",
        10609 => "设计",
        10610 => "摄影",
        10611 => "美术",
        10612 => "作家",
        10701 => "影视",
        10702 => "音乐",
        10703 => "模特",
        10704 => "运动体育",
        10705 => "健身",
        10706 => "演员",
        10707 => "歌手",
        10708 => "运动员",
        10709 => "健身教练",
        10801 => "政府机关",
        10802 => "医疗",
        10803 => "教育",
        10804 => "法律",
        10805 => "科研",
        10806 => "公益",
        10807 => "公务员",
        10808 => "警察",
        10809 => "军人",
        10810 => "医生",
        10811 => "护士",
        10812 => "教师",
        10813 => "幼师",
        10814 => "律师",
        10901 => "市场营销",
        10902 => "策划",
        10903 => "商务",
        10904 => "销售",
        10905 => "公关",
        10906 => "财务",
        10907 => "人力资源",
        10908 => "文员",
        10909 => "行政",
        10910 => "前台",
        11001 => "学生",
        11002 => "大学生",
        11003 => "研究生",
        11004 => "留学生",
        11101 => "自由职业",
        11102 => "无业",
        11103 => "其他",
    );

    private static $_wechat_tags = array(
        1 => array(
//			array('key' => 1, 'val' => '防火防盗防学长'),
            array('key' => 1, 'val' => '智障组合'),
            array('key' => 2, 'val' => 'ex'),
            array('key' => 3, 'val' => '备胎'),
            array('key' => 4, 'val' => '发小'),
            array('key' => 5, 'val' => '闺蜜'),
            array('key' => 6, 'val' => '甲方乙方'),
            array('key' => 7, 'val' => '搬砖工友'),
            array('key' => 8, 'val' => '吃瓜路人'),
            array('key' => 9, 'val' => '仰慕者'),
            array('key' => 10, 'val' => 'soulmate'),
            array('key' => 11, 'val' => '脑残粉'),
            array('key' => 12, 'val' => '吃货联盟'),
            array('key' => 13, 'val' => '树洞'),
            array('key' => 14, 'val' => '颜粉'),
            array('key' => 15, 'val' => '互赞党'),
            array('key' => 16, 'val' => '一起play'),
            array('key' => 17, 'val' => '开黑党'),
            array('key' => 18, 'val' => '长期饭票'),
            array('key' => 19, 'val' => '老司机带我'),
            array('key' => 20, 'val' => '“你爸爸”'),
            array('key' => 21, 'val' => '百合大法'),
            array('key' => 22, 'val' => '直男友情'),
            array('key' => 23, 'val' => '猪队友'),
            array('key' => 24, 'val' => '“包养”'),
            array('key' => 25, 'val' => '同流合“污”'),
            array('key' => 26, 'val' => '亲妈粉'),
            array('key' => 27, 'val' => '后宫'),
            array('key' => 28, 'val' => '相爱相杀'),
            array('key' => 29, 'val' => 'PY交易'),
            array('key' => 30, 'val' => '拖油瓶'),
            array('key' => 31, 'val' => '废柴联盟'),
            array('key' => 32, 'val' => '葛优瘫联盟'),
            array('key' => 33, 'val' => '抄作业联盟'),
            array('key' => 34, 'val' => '老光棍联盟'),
            array('key' => 35, 'val' => '单身狗协会'),
            array('key' => 36, 'val' => 'FFF团'),
            array('key' => 37, 'val' => '迷妹联盟'),
            array('key' => 38, 'val' => '互喂安利'),
            array('key' => 39, 'val' => '宅圈'),
            array('key' => 40, 'val' => '腐圈'),
            array('key' => 41, 'val' => '哈哈党'),
            array('key' => 42, 'val' => '大腿求抱'),
            array('key' => 43, 'val' => '真情互黑'),
            array('key' => 44, 'val' => '捡肥皂'),
            array('key' => 45, 'val' => '哲♂学'),
            array('key' => 46, 'val' => '隔壁老王'),
            array('key' => 47, 'val' => '阿呆与阿瓜'),
            array('key' => 48, 'val' => '大神与小白'),
        ),
        2 => array(
            array('key' => 1, 'val' => '打球时场边加油'),
            array('key' => 2, 'val' => '摸过头'),
            array('key' => 3, 'val' => '安慰过你的眼泪'),
            array('key' => 4, 'val' => '壁咚过'),
            array('key' => 5, 'val' => '曾经靠得很近'),
            array('key' => 6, 'val' => '借过肩膀'),
            array('key' => 7, 'val' => '分享一对耳机'),
            array('key' => 8, 'val' => '为你唱过一首歌'),
            array('key' => 9, 'val' => '夹走过对方碗里的食物'),
            array('key' => 10, 'val' => '深夜陪聊过'),
            array('key' => 11, 'val' => '我们好像很合适'),
            array('key' => 12, 'val' => '一起看过烂片'),
            array('key' => 13, 'val' => '漂洋过海来看你'),
            array('key' => 14, 'val' => '共撑一把伞'),
            array('key' => 15, 'val' => '小礼物什么的'),
            array('key' => 16, 'val' => '有幸成为你的树洞'),
            array('key' => 17, 'val' => '友达以上，恋人未满'),
            array('key' => 18, 'val' => '一起旅行'),
            array('key' => 19, 'val' => '总是心有灵犀'),
            array('key' => 20, 'val' => '电梯偶遇'),
            array('key' => 21, 'val' => '就在隔壁'),
            array('key' => 22, 'val' => '难以忘记初次见你'),
            array('key' => 23, 'val' => '喜欢你很久'),
            array('key' => 24, 'val' => '我们那么合拍'),
            array('key' => 25, 'val' => '一起看过live'),
            array('key' => 26, 'val' => '上次聊天是我主动'),
            array('key' => 27, 'val' => '很久没见面'),
            array('key' => 28, 'val' => '曾经是同学'),
            array('key' => 29, 'val' => '写字比你好看'),
            array('key' => 30, 'val' => '唱歌比你好听'),
            array('key' => 31, 'val' => '吃过我做的菜'),
            array('key' => 32, 'val' => '陪你逛过街'),
            array('key' => 33, 'val' => '斗过表情包'),
            array('key' => 34, 'val' => '那些年没错过一场大雨'),
            array('key' => 35, 'val' => '我们之间存在一种危险关系'),
            array('key' => 36, 'val' => '陪你失恋'),
            array('key' => 37, 'val' => '等你爱我'),
            array('key' => 38, 'val' => '我就是传说中的窝边草'),
            array('key' => 39, 'val' => '我是一棵回头草'),
            array('key' => 40, 'val' => '有最糗的回忆'),
            array('key' => 41, 'val' => '可惜不是你的理想型'),
            array('key' => 42, 'val' => '想把好吃的都送给你'),
            array('key' => 43, 'val' => '朋友式拥抱'),
            array('key' => 44, 'val' => '一起通宵浪过'),
            array('key' => 45, 'val' => '朋友的朋友'),
            array('key' => 46, 'val' => '不知道怎么跟你聊天'),
            array('key' => 47, 'val' => '曾经送你到楼下'),
            array('key' => 48, 'val' => '做过你的救火队员'),
            array('key' => 49, 'val' => '喜欢欺负你'),
            array('key' => 50, 'val' => '认识你很久'),
        ),
        3 => array(
            array('key' => 1, 'val' => '世界第一可爱'),
            array('key' => 2, 'val' => '你看起来很好吃'),
            array('key' => 3, 'val' => '你笑起来很美'),
            array('key' => 4, 'val' => '穿什么都好看！'),
            array('key' => 5, 'val' => '你好香'),
            array('key' => 6, 'val' => 'you are my sunshine'),
            array('key' => 7, 'val' => '“Hi，你是混血吗？”'),
            array('key' => 8, 'val' => '你怎样都好看'),
            array('key' => 9, 'val' => '难以忘记初次见你'),
            array('key' => 11, 'val' => '你总能治愈我'),
            array('key' => 10, 'val' => '从未遇见过你这样的女孩'),
            array('key' => 12, 'val' => '想送你糖'),
            array('key' => 13, 'val' => '想在每个清晨和你拥吻'),
            array('key' => 20, 'val' => '我养你啊'),
            array('key' => 14, 'val' => '春风十里，不如睡你'),
            array('key' => 17, 'val' => '要抱抱'),
            array('key' => 15, 'val' => '累吗？我的肩膀一直在'),
            array('key' => 16, 'val' => '买！'),
            array('key' => 18, 'val' => '想把好吃的都送给你'),
            array('key' => 19, 'val' => '你还没有男朋友？那你现在有了'),
            array('key' => 30, 'val' => '你在我心中永远是第一位的宝宝'),
            array('key' => 21, 'val' => '为你，千千万万遍'),
            array('key' => 22, 'val' => 'You jump, I jump'),
            array('key' => 23, 'val' => '余生请多指教'),
            array('key' => 26, 'val' => '愿把西瓜心里最甜的一勺给你'),
            array('key' => 25, 'val' => '我可以领养你吗'),
            array('key' => 31, 'val' => '你说什么都对！'),
            array('key' => 27, 'val' => '我们可以重新认识吗'),
            array('key' => 24, 'val' => '饿了吗？带你去吃好吃的'),
            array('key' => 28, 'val' => '一想到你呀就让我快乐'),
            array('key' => 29, 'val' => '套路是学的，撩你是真心的'),
            array('key' => 32, 'val' => '你是我的理想型呀'),
            array('key' => 33, 'val' => '不准这么笑，我会动心的'),
            array('key' => 34, 'val' => '你在干嘛？我在想你'),
            array('key' => 35, 'val' => '可以试试把我当成你的世界吗？'),
            array('key' => 36, 'val' => '我坐在秋千上想你，停不下来'),
            array('key' => 37, 'val' => '看不到你的第一秒开始想你'),
            array('key' => 38, 'val' => '除了恋爱我跟你没什么好谈的！'),
            array('key' => 39, 'val' => '宝贝'),
            array('key' => 40, 'val' => '她化了妆也没你化成灰好看'),
            array('key' => 41, 'val' => '说过喜欢你了，要是情况有变，会通知你的'),
            array('key' => 42, 'val' => '除了喜欢你，我什么也做不好'),
            array('key' => 43, 'val' => '喜欢的人，都像你'),
            array('key' => 44, 'val' => '遇见你，然后遇见我自己'),
            array('key' => 45, 'val' => '有我在'),
            array('key' => 46, 'val' => 'I will never hurt you'),
            array('key' => 47, 'val' => '心里空空的，离开你已经6分钟了'),
        ),
        4 => array(
            array('key' => 1, 'val' => '握草有八卦！'),
            array('key' => 2, 'val' => '喂！妖妖灵吗！这里有人欠撩！'),
            array('key' => 3, 'val' => '我只是一只优雅路过的宝宝'),
            array('key' => 4, 'val' => '你这么好看，说什么都对'),
            array('key' => 5, 'val' => '想用笑来伪装掉下的眼泪'),
            array('key' => 6, 'val' => '你开心就好'),
            array('key' => 7, 'val' => '能不能像我这样成熟点？'),
            array('key' => 8, 'val' => '我不会骂你的，我很有修养'),
            array('key' => 9, 'val' => '我差点笑出声，但我忍住了'),
            array('key' => 10, 'val' => '本宝宝不知道你在说什么'),
            array('key' => 11, 'val' => '来来先吃坨shi冷静一下'),
            array('key' => 12, 'val' => '仿佛打开了新世界的大门'),
            array('key' => 13, 'val' => '爱我你怕了吗？'),
            array('key' => 14, 'val' => '如果我是DJ你会爱我吗？'),
            array('key' => 15, 'val' => '我很欣赏你的勇气'),
            array('key' => 16, 'val' => '不能因为露珠超可爱就欺负ta呀'),
            array('key' => 17, 'val' => '还是你们城里人会玩儿'),
            array('key' => 18, 'val' => '醒醒啊你快醒醒！'),
            array('key' => 19, 'val' => '允许你装X，但不要过分'),
            array('key' => 20, 'val' => '来啊来互相伤害啊！'),
            array('key' => 21, 'val' => '我发现你这个人有毒'),
            array('key' => 22, 'val' => '你就是暗恋我！'),
            array('key' => 23, 'val' => 'interesting…'),
            array('key' => 24, 'val' => '世上怎么会有我这么贴心的基友'),
            array('key' => 25, 'val' => '我就看看我不说话'),
            array('key' => 26, 'val' => '你们还是人吗？露珠还是个孩子啊'),
            array('key' => 27, 'val' => '单身狗请在这里排队'),
            array('key' => 28, 'val' => '爱我你怕了吗！'),
            array('key' => 29, 'val' => '露珠为什么这么好看！这是在耽误我！'),
            array('key' => 30, 'val' => '这是今天的药丸，给我吃下去！'),
            array('key' => 31, 'val' => '你不要脸的样子真可爱'),
            array('key' => 32, 'val' => '世界那么大，为何帅的偏偏是我'),
            array('key' => 33, 'val' => '为什么要给我看这些！我还是个孩子！'),
            array('key' => 34, 'val' => '好可怕，我先去搬个小板凳'),
            array('key' => 35, 'val' => '难道世界上只剩我一个单身狗了吗？'),
            array('key' => 36, 'val' => '警察蜀黍就是这个人！'),
            array('key' => 37, 'val' => '我没有奢望，我只想你快乐，没有哀伤。不好意思我先去吐一下'),
            array('key' => 38, 'val' => '如果你在远方承受风雨，而我无能为力，我也会祈祷，让那些风雪降临在我的身上。你问我是不是真心的？当然不是啊怎么还那么天真'),
            array('key' => 39, 'val' => '如果十年之后你未娶，我未嫁，那真的是太惨了！'),
            array('key' => 40, 'val' => '只要你幸福我就幸福这种，都是骗人的'),
            array('key' => 41, 'val' => '你要相信，不到关键时刻ta是不会出现的'),
            array('key' => 42, 'val' => '根据贵圈老少恋的趋势，你的ta可能还在上小学'),
            array('key' => 43, 'val' => '俊凯还没成年，你再等等'),
            array('key' => 44, 'val' => 'Excuse me？不敢相信我的眼睛……'),
            array('key' => 45, 'val' => '你正在承受你这个年龄不该有的美貌'),
            array('key' => 46, 'val' => '没点体重怎么能压住你的美貌'),
            array('key' => 47, 'val' => '打开城门！和平解放单身狗！'),
            array('key' => 48, 'val' => '世界这么大，你在家看看地图就好了'),
            array('key' => 49, 'val' => '有人报警说你有虐狗倾向，请和我到有关部门走一趟'),
            array('key' => 50, 'val' => '你还小，不适合谈恋爱'),
            array('key' => 51, 'val' => '千万不要因为丑而不敢表白，万一ta瞎呢'),
            array('key' => 52, 'val' => '相遇就是最美好的礼物'),
            array('key' => 53, 'val' => '别千年等一回了，该出手时就出手吧'),
            array('key' => 54, 'val' => '不要总是练习一个人了'),
            array('key' => 55, 'val' => '不要太挑了，彦祖挺好的'),
            array('key' => 56, 'val' => '看看眼前的苟且，忘记诗和远方吧'),
            array('key' => 57, 'val' => '虽然你长得丑，但是你想的美'),
            array('key' => 58, 'val' => '喜欢谁就勇敢去表白，没准你还能知道自己是几号备胎'),
            array('key' => 59, 'val' => '今天解决不了的事情不要着急，因为你明天一样没办法解决'),
            array('key' => 60, 'val' => '现在没对象算什么，以后没对象的日子多着呢'),
            array('key' => 61, 'val' => '强扭的瓜不甜，但是有瓜吃呀'),
            array('key' => 62, 'val' => '如果你特别迷恋一个人，那你一定配不上ta'),
            array('key' => 63, 'val' => '就算失败99次，你也要努力凑个整数'),
            array('key' => 66, 'val' => '一见钟情，不过是见色起意'),
            array('key' => 65, 'val' => '你以为你是公主，其实你是白马，王子都是骑着你找公主的'),
            array('key' => 66, 'val' => '不要以为世界抛弃了你，世界根本没空搭理你'),
            array('key' => 67, 'val' => '七个小屌丝对白雪公主再好，也不及高富帅的一个吻'),
            array('key' => 68, 'val' => '就算你能买到充电五分钟的手机，你能找到通话两小时的人吗'),
            array('key' => 69, 'val' => '世界上根本没啥缘分，你自己死皮赖脸点吧'),
            array('key' => 70, 'val' => '我们之间智商上的差距是不可逾越的'),
            array('key' => 71, 'val' => '又一天过去了。今天过得怎么样，是不是更习惯了？'),
            array('key' => 72, 'val' => '要是有个地方能出卖自己的寂寞换取物质享受，我一定是首富'),
            array('key' => 73, 'val' => '单身是个很好的借口，说得好像人家会看上你似的'),
            array('key' => 74, 'val' => '不要总认为明天会更好，明天只会更老。'),
            array('key' => 75, 'val' => '阁下何不乘风起，扶摇直上九万里'),
            array('key' => 76, 'val' => '撩妹这件事终究还是看脸的'),
            array('key' => 77, 'val' => '能把阿玛尼穿成尼玛啊的人，大概只有你了'),
            array('key' => 78, 'val' => '东西坏了就修一修，不要总想着扔，没人给你换的'),
            array('key' => 79, 'val' => '浪里个浪，浪里个浪'),
            array('key' => 80, 'val' => '单身不是你的问题，因为你没得选啊'),
            array('key' => 81, 'val' => '七月初七，凄凄惨惨戚戚'),
            array('key' => 82, 'val' => '红楼隔雨相望冷，珠箔飘灯独自归'),
            array('key' => 83, 'val' => '朋友一生一起走，谁先脱团谁是狗'),
            array('key' => 84, 'val' => '转角遇到爱的故事，只是写给青春期的孩纸看的，过了青春期你还相信，那你就活该一直等待'),
            array('key' => 85, 'val' => '爱情的残忍在于，无论你怎么努力，不爱你就是不爱你'),
        ),
        5 => array(
            array("key" => 1, "val" => "1"),
        )
    );

    private static $_mapLanguage = array(
        array('eid' => 'zh_cn', 'name' => '中文版'),
        array('eid' => 'zh_tw', 'name' => '台湾版'),
        array('eid' => 'en', 'name' => '英文版'),
        array('eid' => 'ar', 'name' => '阿语版'),
        array('eid' => 'ms', 'name' => '马来语版'),
        array('eid' => 'th', 'name' => '泰语版'),
        array('eid' => 'id', 'name' => '印尼语版'),
        array('eid' => 'vi', 'name' => '越南语版'),
        array('eid' => 'ko', 'name' => '韩语版'),
        array('eid' => 'ja', 'name' => '日语版'),
        array('eid' => 'hi', 'name' => '印地语版'),
        array('eid' => 'bn', 'name' => '孟加拉语版'),
        array('eid' => 'ur', 'name' => '乌尔都语版'),
        array('eid' => 'tr', 'name' => '土耳其语版'),
    );

    public static function getUserSex($key)
    {
        if (isset(self::$_mapUserSex[$key])) {
            return self::$_mapUserSex[$key];
        }
        return "";
    }

    public static function getUserPurpose($key)
    {
        if (isset(self::$_mapUserPurpose[$key])) {
            return self::$_mapUserPurpose[$key];
        }
        return "";
    }

    public static function getUserEmotionState($key)
    {
        if (isset(self::$_mapUserEmotionState[$key])) {
            return self::$_mapUserEmotionState[$key];
        }
        return "";
    }

    public static function getUserEdu($key)
    {
        if (isset(self::$_mapUserEdu[$key])) {
            return self::$_mapUserEdu[$key];
        }
        return "";
    }

    public static function getUserJob($key)
    {
        if (isset(self::$_mapUserJob[$key])) {
            return self::$_mapUserJob[$key];
        }
        return "";
    }

    public static function getUserJobData()
    {
        return self::$_mapUserJob;
    }

    public static function getUserEduData()
    {
        return self::$_mapUserEdu;
    }

    public static function getUserGrade($key)
    {
        if (isset(self::$_mapUserGrade[$key])) {
            return self::$_mapUserGrade[$key];
        }
        return "";
    }

    public static function getWechatTags($play)
    {
        if (isset(self::$_wechat_tags[$play])) {
            return self::$_wechat_tags[$play];
        }
        return array();
    }

    public static function getLanguage()
    {
        return self::$_mapLanguage;
    }
}