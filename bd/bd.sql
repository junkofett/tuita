
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
  fecha       date         not null default CURRENT_DATE
);
  