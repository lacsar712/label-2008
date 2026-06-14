FROM php:8.1-apache

# 设置环境变量
ENV LANG=C.UTF-8 \
    LC_ALL=C.UTF-8

# 安装 mysqli 扩展
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 启用 Apache mod_rewrite
RUN a2enmod rewrite

# 设置工作目录
WORKDIR /var/www/html

# 复制应用文件
COPY ./www /var/www/html

# 设置权限
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
