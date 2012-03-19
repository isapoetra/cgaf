drop view if exists `vw_userinfo`;
drop view if exists `vw_user_state`;
drop view if exists `vw_user_status`;
CREATE VIEW `vw_userinfo` AS
	select u.*,
		concat(`p`.`first_name`,' ',`p`.`last_name`) AS `fullname`,
		`p`.`birth_date` AS `birth_date`,
		`c`.`company_id` AS `company_id`,
		`c`.`company_name` AS `company_name`
	from (((`users` `u` left join `persons` `p` on((`p`.`person_owner` = `u`.`user_id` and p.isprimary=true)))
	  left join `user_companies` `uc` on((`u`.`user_id` = `uc`.`user_id`)))
	 left join `companies` `c` on((`uc`.`company_id` = `c`.`company_id`)));
CREATE VIEW `vw_user_state` AS select `lookup`.`key` AS `key`,`lookup`.`value` AS `value` from `lookup` where ((`lookup`.`app_id` = '__cgaf') and (`lookup`.`lookup_id` = 'user_state'));
CREATE VIEW `vw_user_status` AS select `lookup`.`key` AS `key`,`lookup`.`value` AS `value` from `lookup` where ((`lookup`.`app_id` = '__cgaf') and (`lookup`.`lookup_id` = 'user_status'));
INSERT INTO `#__users` (`user_id`,`user_name`,`user_password`,`user_status`,`user_state`) VALUES	(-1, 'guest', NULL, 0, 999);
