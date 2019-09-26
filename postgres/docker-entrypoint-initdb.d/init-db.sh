#!/bin/bash

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "create extension LTREE;" homestead
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "
create table tree
(
    id serial not null,
    path ltree,
    title varchar(255) not null,
    price double precision not null
);" homestead

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "CREATE INDEX path_gist_idx ON tree USING GIST (path);" homestead
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "CREATE INDEX path_idx ON tree USING BTREE (path);" homestead

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "create unique index tree_id_uindex
    on tree (id);" homestead

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "create unique index tree_position_uindex
    on tree (path);" homestead

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "alter table tree
    add constraint tree_pk
        primary key (id);
" homestead

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "
create table simple
(
    id        serial           not null
        constraint simple_pk
            primary key,
    position  varchar(255) not null,
    title     varchar(255) not null,
    price     double precision not null,
    parent_id integer
        constraint simple_simple_id_fk
            references simple
            on delete cascade
);" homestead

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "alter table simple
    owner to homestead;" homestead

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "create unique index simple_id_uindex
    on simple (id);" homestead