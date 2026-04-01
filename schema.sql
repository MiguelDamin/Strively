-- WARNING: This schema is for context only and is not meant to be run.
-- Table order and constraints may not be valid for execution.

CREATE TABLE public.comentarios (
  id integer NOT NULL DEFAULT nextval('comentarios_id_seq'::regclass),
  usuario_id integer NOT NULL,
  equipamento_id integer NOT NULL,
  texto text NOT NULL,
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT comentarios_pkey PRIMARY KEY (id),
  CONSTRAINT comentarios_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id),
  CONSTRAINT comentarios_equipamento_id_fkey FOREIGN KEY (equipamento_id) REFERENCES public.equipamentos(id)
);
CREATE TABLE public.equipamentos (
  id integer NOT NULL DEFAULT nextval('equipamentos_id_seq'::regclass),
  usuario_id integer NOT NULL,
  tipo character varying NOT NULL CHECK (tipo::text = ANY (ARRAY['desconto'::character varying, 'review'::character varying]::text[])),
  produto character varying NOT NULL,
  loja character varying,
  link character varying,
  descricao text NOT NULL,
  foto character varying,
  upvotes integer NOT NULL DEFAULT 0,
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT equipamentos_pkey PRIMARY KEY (id),
  CONSTRAINT equipamentos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id)
);
CREATE TABLE public.eventos (
  id integer NOT NULL DEFAULT nextval('eventos_id_seq'::regclass),
  usuario_id integer NOT NULL,
  nome character varying NOT NULL,
  cidade character varying NOT NULL,
  data_evento date NOT NULL,
  distancias character varying,
  descricao text,
  link_oficial character varying,
  banner character varying,
  status character varying NOT NULL DEFAULT 'ativo'::character varying CHECK (status::text = ANY (ARRAY['ativo'::character varying, 'inativo'::character varying]::text[])),
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT eventos_pkey PRIMARY KEY (id),
  CONSTRAINT eventos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id)
);
CREATE TABLE public.notificacoes (
  id integer NOT NULL DEFAULT nextval('notificacoes_id_seq'::regclass),
  usuario_id integer NOT NULL,
  texto character varying NOT NULL,
  link character varying,
  lida boolean NOT NULL DEFAULT false,
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT notificacoes_pkey PRIMARY KEY (id),
  CONSTRAINT notificacoes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id)
);
CREATE TABLE public.treinadores (
  id integer NOT NULL DEFAULT nextval('treinadores_id_seq'::regclass),
  usuario_id integer NOT NULL UNIQUE,
  cref character varying,
  faculdade character varying,
  assessoria character varying,
  especialidade character varying,
  diploma_path character varying,
  status character varying NOT NULL DEFAULT 'pendente'::character varying CHECK (status::text = ANY (ARRAY['pendente'::character varying, 'aprovado'::character varying, 'reprovado'::character varying]::text[])),
  motivo_reprovacao text,
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT treinadores_pkey PRIMARY KEY (id),
  CONSTRAINT treinadores_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id)
);
CREATE TABLE public.treinos (
  id integer NOT NULL DEFAULT nextval('treinos_id_seq'::regclass),
  treinador_id integer NOT NULL,
  aluno_id integer NOT NULL,
  titulo character varying NOT NULL,
  descricao text NOT NULL,
  data_treino date NOT NULL,
  tipo character varying NOT NULL DEFAULT 'unico'::character varying CHECK (tipo::text = ANY (ARRAY['unico'::character varying, 'planilha'::character varying]::text[])),
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT treinos_pkey PRIMARY KEY (id),
  CONSTRAINT treinos_treinador_id_fkey FOREIGN KEY (treinador_id) REFERENCES public.usuarios(id),
  CONSTRAINT treinos_aluno_id_fkey FOREIGN KEY (aluno_id) REFERENCES public.usuarios(id)
);
CREATE TABLE public.usuarios (
  id integer NOT NULL DEFAULT nextval('usuarios_id_seq'::regclass),
  nome character varying NOT NULL,
  email character varying NOT NULL UNIQUE,
  senha character varying NOT NULL,
  perfil character varying NOT NULL DEFAULT 'corredor'::character varying CHECK (perfil::text = ANY (ARRAY['corredor'::character varying, 'treinador'::character varying]::text[])),
  status character varying NOT NULL DEFAULT 'ativo'::character varying CHECK (status::text = ANY (ARRAY['ativo'::character varying, 'inativo'::character varying, 'banido'::character varying]::text[])),
  foto character varying,
  cidade character varying,
  treinador_id integer,
  status_vinculo character varying DEFAULT NULL::character varying CHECK (status_vinculo::text = ANY (ARRAY['pendente'::character varying, 'aceito'::character varying, NULL::character varying]::text[])),
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT usuarios_pkey PRIMARY KEY (id),
  CONSTRAINT usuarios_treinador_id_fkey FOREIGN KEY (treinador_id) REFERENCES public.usuarios(id)
);