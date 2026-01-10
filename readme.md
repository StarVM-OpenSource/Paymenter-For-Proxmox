Proxmox扩展插件 for Paymenter

此插件将Proxmox虚拟化管理平台与Paymenter系统集成，提供高效的虚拟服务器管理功能。通过该插件，用户能够方便地在Paymenter平台上管理Proxmox服务器，实现自动化管理、资源监控和更多功能。

功能特性：

虚拟机管理

支持 KVM 虚拟机 和 LXC 容器

自动开通、暂停、恢复、删除虚拟机

基于模板克隆，快速部署

用户自助服务

VNC 浏览器控制台访问

电源控制（开机/关机/重启）

一键系统重装

密码重置

备份创建与恢复

网络功能

IPv4/IPv6 双栈支持

IP 池管理

NAT 端口转发（共享公网 IP）

网络限速

月流量限制与监控

智能分配 IP

随机分配

顺序填充

负载均衡（按内存/磁盘/CPU）

系统要求

Paymenter: v1.0+

Proxmox VE: 7.0+

PHP: 8.1+（需 ssh2 扩展）

安装说明：

进入Paymenter管理后台，前往“扩展”>“安装扩展”，上传下载的zip文件。

安装完成后，前往“扩展”>“管理扩展”，启用Proxmox插件。

文档与支持：

[Proxmox插件文档 ](https://5ssr.com/help/article/paymenter-for-pve)

更多信息：

官方网站：[5SSR.COM](https://5ssr.com/)

官方Telegram群：[ssrpingcequn](https://t.me/ssrpingcequn)
