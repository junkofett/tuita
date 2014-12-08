--USUARIOS
insert into usuarios (nick, password)
            values('megaman', md5('megaman'));
insert into usuarios (nick, password)
            values ('antuan', md5('antuan'));
insert into usuarios (nick, password)
            values ('tete', md5('tete'));
insert into usuarios (nick, password)
            values ('laura', md5('laura'));

--TUITS
insert into tuits (mensaje, usuarios_id, fecha)
            values ('@antuan olacaracaola',1, to_timestamp('2014-12-06 22:00:23.022424', 'YYYY-MM-DD HH24:MI:SS.US'));
insert into tuits (mensaje, usuarios_id, fecha)
            values ('@megaman tetetetetete', 2, to_timestamp('2014-12-06 22:03:49.022424', 'YYYY-MM-DD HH24:MI:SS.US'));
insert into tuits (mensaje, usuarios_id, fecha)
            values ('@antuan illo q ase q maburro', 1, to_timestamp('2014-12-06 22:04:09.022424', 'YYYY-MM-DD HH24:MI:SS.US'));
insert into tuits (mensaje, usuarios_id, fecha)
            values ('@megaman programar php', 2, to_timestamp('2014-12-06 22:05:26.022424', 'YYYY-MM-DD HH24:MI:SS.US'));
insert into tuits (mensaje, usuarios_id, fecha)
            values ('@antuan teskilla maricona #notelocreenitu', 1, to_timestamp('2014-12-06 22:05:44.022424', 'YYYY-MM-DD HH24:MI:SS.US'));
insert into tuits (mensaje, usuarios_id, fecha)
            values ('@megaman que ji cohone #nonina', 2, to_timestamp('2014-12-06 22:05:55.022424', 'YYYY-MM-DD HH24:MI:SS.US'));

--RELACIONADOS
insert into relacionados (id_usuarios_mencionados, tuits_id)
			values(2,1);
insert into relacionados (id_usuarios_mencionados, tuits_id)
			values(1,2);
insert into relacionados (id_usuarios_mencionados, tuits_id)
			values(2,3);
insert into relacionados (id_usuarios_mencionados, tuits_id)
			values(1,4);
insert into relacionados (id_usuarios_mencionados, tuits_id)
			values(2,5);
insert into relacionados (id_usuarios_mencionados, tuits_id)
			values(1,6);

--HASHTAGS
insert into hashtags (nombre)
			values('notelocreenitu');
insert into hashtags (nombre)
			values('nonina');

--HASHTAGS EN TUITS
insert into hashtags_en_tuits (hashtags_id, tuits_id)
			values (1,5);
insert into hashtags_en_tuits (hashtags_id, tuits_id)
			values (2,6);
