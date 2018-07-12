# FSlmx
FreeSWITCH GUI 简体中文GUI for PHP (UTF8)

本系统是对FreeSWITCH进行管理配置的，基于FS的ESL和修改管理FS配置文件来实现相关辅助管理；
1、本系统完全是FS的辅助管理，因为FS没有一个简单、开源、可用、够用的中文GUI来实现FS的全面管理（不考虑那些为利益而假开源的项目）
2、基于上面，本系统调用FSL的FS命令控制FS，通过php对FS的配置文件进行文件的修改管理，不对FS的进行任何修改和调整；保证了FS的完整和独立
3、建议对FS有基本的了解，再使用这个系统，因为它仅是辅助管理的

安装：
1、安装FS，这按FS的安装说明 https://freeswitch.org/confluence/display/FREESWITCH/Installation ，
我的个人安装小记 http://blog.sina.com.cn/s/blog_539d6e0c0102zgvm.html

2、本系统的安装使用，需确保FS的ESL可用，否则无法进行FS的管理控制；这提供两种方式，1个是允许外部IP连接到event_socket，1个是设置domains和cidr，允许连入的IP，具体配置的我的个人安装小记有说明

3、为实现对FS配置文件的管理，需要确保php有权限读写FS配置文件，所以必须设置好权限（让FS和php都保持统一的用户）

4、数据库是mysql，数据库定义在SQL目录；CDR是选用xmlCDR
