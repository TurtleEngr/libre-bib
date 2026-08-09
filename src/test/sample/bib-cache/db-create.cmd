create database biblio_example;

create user 'admin'@'localhost' identified by 'demo';
grant all privileges on *.* to 'admin'@'localhost';

create user 'bruce'@'localhost' identified by 'demo';
grant all privileges on biblio_example.* to 'bruce'@'localhost';

flush privileges;

show databases;
select host,user from mysql.user;
show grants for 'root'@localhost;
show grants for 'admin'@localhost;
show grants for 'bruce'@localhost;
