#制作框架所需的环境
FROM ubuntu:18.04

MAINTAINER johnzhang 875010341@qq.com
#声明基本信息
ENV TZ=American/Los_Angeles DEBIAN_FRONTEND=noninteractive PUBLISH_AT=2019-12-25-01 \
APACHE_RUN_DIR=/var/run/apache2 APACHE_RUN_USER=www-data APACHE_RUN_GROUP=www-data \
APACHE_LOCK_DIR=/var/lock/apache2 APACHE_LOG_DIR=/var/log/apache2 APP_ENV=local
#首先更新镜像信息
RUN apt-get update
#安装apache+php7.2
RUN apt-get install -y curl git apache2 composer php7.2 php7.2-cli php7.2-gd php7.2-curl php7.2-mbstring php7.2-bcmath \
php-redis php7.2-opcache && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
#开启一些配置
RUN a2enmod ssl rewrite env

VOLUME ["/var/www/framework", "/etc/apache2/sites-enabled"]
EXPOSE 80
#运行服务
ENTRYPOINT ["/usr/sbin/apache2", "-D", "FOREGROUND"]
