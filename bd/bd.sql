
drop table if exists usuarios cascade;

create table usuarios(
  id        bigserial     constraint pk_usuarios primary key,
  nick      varchar(15)   not null constraint uq_usuarios_nick unique,
  password  char(32)      not null constraint ck_password_valida
                           check (length(password) = 32)
);

drop table if exists tuits cascade;

create table tuits(
  id          bigserial     constraint pk_tuits primary key,
  mensaje     varchar(140)  not null,
  usuarios_id bigint        not null constraint fk_usuarios_id
                              references usuarios(id) on delete no action
                              on update cascade,
  fecha       timestamp     not null default current_timestamp
);

drop table if exists relacionados cascade;

create table relacionados(
  id_usuarios_mencionados   bigint  not null constraint fk_usuarios_id
                                      references usuarios(id) on delete no action
                                      on update cascade,
  tuits_id                  bigint  not null constraint fk_tuits_id
                                      references tuits(id) on delete no action
                                      on update cascade,

  constraint pk_relacionados primary key (id_usuarios_mencionados, tuits_id)
);

drop table if exists hashtags cascade;

create table hashtags(
  id      bigserial   constraint pk_hashtags primary key,
  nombre  varchar(24) not null constraint uq_hash_nombre unique
);

drop table if exists hashtags_en_tuits;

create table hashtags_en_tuits(
  hashtags_id bigint  not null constraint fk_hash_id
                        references hashtags(id) on delete no action
                        on update cascade,
  tuits_id    bigint  not null constraint fk_tuits_id
                        references tuits(id) on delete no action
                        on update cascade,

  constraint pk_hash_en_tuit primary key (hashtags_id, tuits_id)
);