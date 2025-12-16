#审核业务 看板
00 07 * * * php /home/webroot/bms-partying-admin-new/cli.php kanban -op verifyKanban >> /home/log/bms-partystar-admin-new/crontab/newVerifyKanban.log 2>&1
# 每天1点 统计幸运玩法数据
0 1 * * * php /home/webroot/bms-partystar-admin-new/cli.php cronplay -process 1 >> /home/log/bms-partystar-admin-new/crontab/cronplay.log 2>&1
#新p90统计脚本
03 08 * * * php /home/webroot/bms-partystar-admin-new/cli.php kanban -op quartileNew >> /home/log/bms-partystar-admin-new/crontab/quartileNew.cron.log 2>&1
#新p90计算缓存
33 08 * * * php /home/webroot/bms-partystar-admin-new/cli.php kanban -op quartileCache >> /home/log/bms-partystar-admin-new/crontab/quartileNewCache.cron.log 2>&1
#定时推送push
*/1 * * * * php /home/webroot/bms-partystar-admin/cli.php delaypush >> /home/log/bms-partystar-admin-new/crontab/delaypush.log 2>&1
#清除离职人员
30 * * * * php /home/webroot/bms-partystar-admin-new/cli.php clearleave >> /home/log/bms-partystar-admin-new/crontab/clearleave.cron.log 2>&1
# ka用户列表
30 1 * * * php /home/webroot/bms-partystar-admin-new/cli.php ka -process kaUserList >> /home/log/bms-partystar-admin-new/crontab/ka.kaUserList.cron.log 2>&1
# quartileSummit
35 07 * * * php /home/webroot/bms-partystar-admin-new/cli.php kanban -op quartileSummit >> /home/log/crontab/quartileSummit_ps.log 2>&1