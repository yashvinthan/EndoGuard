--
-- PostgreSQL database dump
--

\restrict PNIxVmQt3XjQfLdpwqBqtJrnJebjdhg37Yz3jQGNe5XEwkTTjMjeqVyThOeM0rL

-- Dumped from database version 15.15
-- Dumped by pg_dump version 15.15

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: citext; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS citext WITH SCHEMA public;


--
-- Name: EXTENSION citext; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION citext IS 'data type for case-insensitive character strings';


--
-- Name: pg_stat_statements; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_stat_statements WITH SCHEMA public;


--
-- Name: EXTENSION pg_stat_statements; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_stat_statements IS 'track planning and execution statistics of all SQL statements executed';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: blacklist_type; Type: TYPE; Schema: public; Owner: endoguard
--

CREATE TYPE public.blacklist_type AS ENUM (
    'email',
    'phone',
    'ip'
);


ALTER TYPE public.blacklist_type OWNER TO endoguard;

--
-- Name: dshb_operators_change_email_status; Type: TYPE; Schema: public; Owner: endoguard
--

CREATE TYPE public.dshb_operators_change_email_status AS ENUM (
    'unused',
    'used',
    'invalidated'
);


ALTER TYPE public.dshb_operators_change_email_status OWNER TO endoguard;

--
-- Name: dshb_operators_forgot_password_status; Type: TYPE; Schema: public; Owner: endoguard
--

CREATE TYPE public.dshb_operators_forgot_password_status AS ENUM (
    'unused',
    'used',
    'invalidated'
);


ALTER TYPE public.dshb_operators_forgot_password_status OWNER TO endoguard;

--
-- Name: email; Type: DOMAIN; Schema: public; Owner: endoguard
--

CREATE DOMAIN public.email AS public.citext
	CONSTRAINT email_check CHECK ((VALUE OPERATOR(public.~) '^[a-zA-Z0-9.!#$%&''*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$'::public.citext));


ALTER DOMAIN public.email OWNER TO endoguard;

--
-- Name: queue_account_operation_action; Type: TYPE; Schema: public; Owner: endoguard
--

CREATE TYPE public.queue_account_operation_action AS ENUM (
    'blacklist',
    'delete',
    'calculate_risk_score',
    'enrichment'
);


ALTER TYPE public.queue_account_operation_action OWNER TO endoguard;

--
-- Name: queue_account_operation_status; Type: TYPE; Schema: public; Owner: endoguard
--

CREATE TYPE public.queue_account_operation_status AS ENUM (
    'waiting',
    'executing',
    'completed',
    'failed'
);


ALTER TYPE public.queue_account_operation_status OWNER TO endoguard;

--
-- Name: unreviewed_items_reminder_frequency; Type: TYPE; Schema: public; Owner: endoguard
--

CREATE TYPE public.unreviewed_items_reminder_frequency AS ENUM (
    'daily',
    'weekly',
    'off'
);


ALTER TYPE public.unreviewed_items_reminder_frequency OWNER TO endoguard;

--
-- Name: dshb_api_co_owners_creator_check(); Type: FUNCTION; Schema: public; Owner: endoguard
--

CREATE FUNCTION public.dshb_api_co_owners_creator_check() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM dshb_api WHERE creator = NEW.operator)
    THEN
        RAISE EXCEPTION 'A row with operator % already exists in the dshb_api table''s creator column', NEW.operator;
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.dshb_api_co_owners_creator_check() OWNER TO endoguard;

--
-- Name: event_lastseen(); Type: FUNCTION; Schema: public; Owner: endoguard
--

CREATE FUNCTION public.event_lastseen() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.lastseen < OLD.lastseen THEN
        NEW.lastseen := OLD.lastseen;
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.event_lastseen() OWNER TO endoguard;

--
-- Name: queue_new_events_cursor_check(); Type: FUNCTION; Schema: public; Owner: endoguard
--

CREATE FUNCTION public.queue_new_events_cursor_check() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF (SELECT COUNT(*) FROM queue_new_events_cursor) > 0 THEN
        RAISE EXCEPTION 'A row in this table already exists';
    ELSE
        RETURN NEW;
    END IF;
END;
$$;


ALTER FUNCTION public.queue_new_events_cursor_check() OWNER TO endoguard;

--
-- Name: restrict_update(); Type: FUNCTION; Schema: public; Owner: endoguard
--

CREATE FUNCTION public.restrict_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
   IF NEW.key <> OLD.key THEN
      RAISE EXCEPTION 'not allowed';
   END IF;
   RETURN NEW;
END;
$$;


ALTER FUNCTION public.restrict_update() OWNER TO endoguard;

--
-- Name: search_whole_db(text); Type: FUNCTION; Schema: public; Owner: endoguard
--

CREATE FUNCTION public.search_whole_db(_like_pattern text) RETURNS TABLE(_tbl regclass, _ctid tid)
    LANGUAGE plpgsql
    AS $_$
BEGIN
   FOR _tbl IN
      SELECT c.oid::regclass
      FROM   pg_class c
      JOIN   pg_namespace n ON n.oid = c.relnamespace
      WHERE  c.relkind = 'r'                           -- only tables
      AND    n.nspname !~ '^(pg_|information_schema)'  -- exclude system schemas
      ORDER BY n.nspname, c.relname
   LOOP
      RETURN QUERY EXECUTE format(
         'SELECT $1, ctid FROM %s t WHERE t::text ~~ %L',
         _tbl, '%' || _like_pattern || '%'
      );
   END LOOP;
END;
$_$;


ALTER FUNCTION public.search_whole_db(_like_pattern text) OWNER TO endoguard;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: countries; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.countries (
    iso character varying(64) NOT NULL,
    value character varying(64) NOT NULL,
    id integer NOT NULL
);


ALTER TABLE public.countries OWNER TO endoguard;

--
-- Name: dshb_api; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_api (
    id integer NOT NULL,
    key text NOT NULL,
    quote integer NOT NULL,
    creator bigint NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    skip_enriching_attributes jsonb DEFAULT '[]'::jsonb NOT NULL,
    retention_policy smallint DEFAULT 0 NOT NULL,
    skip_blacklist_sync boolean DEFAULT true NOT NULL,
    token character varying,
    last_call_reached boolean,
    blacklist_threshold integer DEFAULT '-1'::integer,
    review_queue_threshold integer DEFAULT 33
);


ALTER TABLE public.dshb_api OWNER TO endoguard;

--
-- Name: dshb_api_co_owners; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_api_co_owners (
    operator bigint NOT NULL,
    api bigint NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.dshb_api_co_owners OWNER TO endoguard;

--
-- Name: dshb_api_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.dshb_api_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dshb_api_id_seq OWNER TO endoguard;

--
-- Name: dshb_api_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.dshb_api_id_seq OWNED BY public.dshb_api.id;


--
-- Name: dshb_logs; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_logs (
    id bigint NOT NULL,
    text text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.dshb_logs OWNER TO endoguard;

--
-- Name: dshb_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.dshb_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dshb_logs_id_seq OWNER TO endoguard;

--
-- Name: dshb_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.dshb_logs_id_seq OWNED BY public.dshb_logs.id;


--
-- Name: dshb_manual_check_history; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_manual_check_history (
    id bigint NOT NULL,
    operator bigint NOT NULL,
    type text NOT NULL,
    search_query text NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.dshb_manual_check_history OWNER TO endoguard;

--
-- Name: dshb_manual_check_history_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.dshb_manual_check_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dshb_manual_check_history_id_seq OWNER TO endoguard;

--
-- Name: dshb_manual_check_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.dshb_manual_check_history_id_seq OWNED BY public.dshb_manual_check_history.id;


--
-- Name: dshb_message; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_message (
    id bigint NOT NULL,
    text text,
    title text,
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE public.dshb_message OWNER TO endoguard;

--
-- Name: dshb_message_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.dshb_message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dshb_message_id_seq OWNER TO endoguard;

--
-- Name: dshb_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.dshb_message_id_seq OWNED BY public.dshb_message.id;


--
-- Name: dshb_operators; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_operators (
    id bigint NOT NULL,
    email public.citext NOT NULL,
    password text,
    firstname text,
    lastname text,
    city text,
    state text,
    zip text,
    country text,
    company text,
    vat text,
    is_active smallint DEFAULT 0,
    activation_key text,
    created_at timestamp with time zone DEFAULT now(),
    street text,
    is_closed smallint DEFAULT 0,
    timezone text DEFAULT 'UTC'::text NOT NULL,
    review_queue_cnt integer,
    review_queue_updated_at timestamp without time zone,
    last_event_time timestamp without time zone,
    unreviewed_items_reminder_freq public.unreviewed_items_reminder_frequency DEFAULT 'weekly'::public.unreviewed_items_reminder_frequency NOT NULL,
    last_unreviewed_items_reminder timestamp without time zone,
    blacklist_users_cnt integer
);


ALTER TABLE public.dshb_operators OWNER TO endoguard;

--
-- Name: dshb_operators_forgot_password; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_operators_forgot_password (
    id bigint NOT NULL,
    operator_id bigint NOT NULL,
    renew_key text NOT NULL,
    status public.dshb_operators_forgot_password_status DEFAULT 'unused'::public.dshb_operators_forgot_password_status NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.dshb_operators_forgot_password OWNER TO endoguard;

--
-- Name: dshb_operators_forgot_password_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.dshb_operators_forgot_password_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dshb_operators_forgot_password_id_seq OWNER TO endoguard;

--
-- Name: dshb_operators_forgot_password_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.dshb_operators_forgot_password_id_seq OWNED BY public.dshb_operators_forgot_password.id;


--
-- Name: dshb_operators_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.dshb_operators_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dshb_operators_id_seq OWNER TO endoguard;

--
-- Name: dshb_operators_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.dshb_operators_id_seq OWNED BY public.dshb_operators.id;


--
-- Name: dshb_operators_rules; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_operators_rules (
    id bigint NOT NULL,
    value integer DEFAULT 0,
    created_at timestamp with time zone DEFAULT now(),
    key integer,
    proportion real,
    proportion_updated_at timestamp without time zone,
    rule_uid character varying
);


ALTER TABLE public.dshb_operators_rules OWNER TO endoguard;

--
-- Name: dshb_operators_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.dshb_operators_rules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dshb_operators_rules_id_seq OWNER TO endoguard;

--
-- Name: dshb_operators_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.dshb_operators_rules_id_seq OWNED BY public.dshb_operators_rules.id;


--
-- Name: dshb_rules; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_rules (
    validated boolean DEFAULT false NOT NULL,
    uid character varying NOT NULL,
    name character varying NOT NULL,
    descr character varying NOT NULL,
    attributes jsonb DEFAULT '[]'::jsonb NOT NULL,
    updated timestamp without time zone DEFAULT now() NOT NULL,
    missing boolean
);


ALTER TABLE public.dshb_rules OWNER TO endoguard;

--
-- Name: dshb_sessions; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_sessions (
    session_id character varying(255) NOT NULL,
    data text,
    ip character varying(45),
    agent character varying(300),
    stamp integer
);


ALTER TABLE public.dshb_sessions OWNER TO endoguard;

--
-- Name: dshb_updates; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.dshb_updates (
    id bigint NOT NULL,
    service character varying(30),
    version character varying(30),
    created timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.dshb_updates OWNER TO endoguard;

--
-- Name: dshb_updates_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.dshb_updates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dshb_updates_id_seq OWNER TO endoguard;

--
-- Name: dshb_updates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.dshb_updates_id_seq OWNED BY public.dshb_updates.id;


--
-- Name: event; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event (
    id bigint NOT NULL,
    key smallint NOT NULL,
    account bigint NOT NULL,
    ip bigint NOT NULL,
    url bigint NOT NULL,
    device bigint NOT NULL,
    "time" timestamp(3) without time zone NOT NULL,
    query bigint,
    traceid character varying(36) DEFAULT NULL::character varying,
    referer bigint,
    type smallint DEFAULT 1 NOT NULL,
    email integer,
    phone integer,
    http_code smallint,
    http_method smallint,
    session_id bigint NOT NULL,
    payload bigint
);


ALTER TABLE public.event OWNER TO endoguard;

--
-- Name: event_account; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_account (
    id bigint NOT NULL,
    userid character varying(100) NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    key integer,
    lastip inet,
    lastseen timestamp without time zone NOT NULL,
    fullname text,
    is_important boolean DEFAULT false NOT NULL,
    firstname character varying(100),
    middlename character varying(100),
    lastname character varying(100),
    total_visit integer DEFAULT 0,
    total_country integer DEFAULT 0,
    total_ip integer DEFAULT 0,
    total_device integer DEFAULT 0,
    score_updated_at timestamp without time zone,
    score integer,
    score_details jsonb,
    lastemail integer,
    lastphone integer,
    total_shared_ip integer DEFAULT 0,
    total_shared_phone integer DEFAULT 0,
    reviewed boolean DEFAULT false,
    fraud boolean,
    latest_decision timestamp without time zone,
    score_recalculate boolean DEFAULT true,
    session_id bigint,
    updated timestamp without time zone NOT NULL,
    added_to_review timestamp without time zone
);


ALTER TABLE public.event_account OWNER TO endoguard;

--
-- Name: event_account_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_account_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_account_id_seq OWNER TO endoguard;

--
-- Name: event_account_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_account_id_seq OWNED BY public.event_account.id;


--
-- Name: event_country; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_country (
    id bigint NOT NULL,
    key integer NOT NULL,
    country integer NOT NULL,
    total_visit integer DEFAULT 0,
    total_ip integer DEFAULT 0,
    total_account integer DEFAULT 0,
    lastseen timestamp without time zone,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated timestamp without time zone NOT NULL
);


ALTER TABLE public.event_country OWNER TO endoguard;

--
-- Name: event_country_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_country_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_country_id_seq OWNER TO endoguard;

--
-- Name: event_country_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_country_id_seq OWNED BY public.event_country.id;


--
-- Name: event_device; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_device (
    id bigint NOT NULL,
    account_id bigint NOT NULL,
    key smallint NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    lastseen timestamp without time zone DEFAULT '1970-01-01 00:00:00'::timestamp without time zone NOT NULL,
    updated timestamp without time zone NOT NULL,
    user_agent bigint NOT NULL,
    lang text,
    total_visit integer DEFAULT 0
);


ALTER TABLE public.event_device OWNER TO endoguard;

--
-- Name: event_device_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_device_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_device_id_seq OWNER TO endoguard;

--
-- Name: event_device_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_device_id_seq OWNED BY public.event_device.id;


--
-- Name: event_domain; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_domain (
    id bigint NOT NULL,
    key smallint NOT NULL,
    domain text,
    ip inet,
    geo_ip character varying(9) DEFAULT NULL::character varying,
    geo_html character varying(9) DEFAULT NULL::character varying,
    web_server character varying(36) DEFAULT NULL::character varying,
    hostname text DEFAULT NULL::character varying,
    emails text,
    phone character varying(19) DEFAULT NULL::character varying,
    discovery_date timestamp without time zone,
    blockdomains boolean,
    disposable_domains boolean,
    total_visit integer DEFAULT 0,
    total_account integer DEFAULT 0,
    lastseen timestamp without time zone NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated timestamp without time zone NOT NULL,
    free_email_provider boolean,
    tranco_rank integer,
    creation_date timestamp without time zone,
    expiration_date timestamp without time zone,
    return_code integer,
    closest_snapshot text,
    checked boolean,
    mx_record boolean,
    disabled boolean
);


ALTER TABLE public.event_domain OWNER TO endoguard;

--
-- Name: event_domain_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_domain_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_domain_id_seq OWNER TO endoguard;

--
-- Name: event_domain_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_domain_id_seq OWNED BY public.event_domain.id;


--
-- Name: event_email; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_email (
    id bigint NOT NULL,
    account_id bigint NOT NULL,
    email public.email,
    lastseen timestamp without time zone NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    key integer DEFAULT 0 NOT NULL,
    checked boolean,
    data_breach boolean,
    profiles integer,
    blockemails boolean,
    domain_contact_email boolean,
    domain bigint,
    fraud_detected boolean DEFAULT false NOT NULL,
    hash text,
    alert_list boolean,
    data_breaches integer,
    earliest_breach text
);


ALTER TABLE public.event_email OWNER TO endoguard;

--
-- Name: event_email_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_email_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_email_id_seq OWNER TO endoguard;

--
-- Name: event_email_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_email_id_seq OWNED BY public.event_email.id;


--
-- Name: event_error_type; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_error_type (
    id smallint NOT NULL,
    value text NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.event_error_type OWNER TO endoguard;

--
-- Name: event_field_audit; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_field_audit (
    id bigint NOT NULL,
    key smallint NOT NULL,
    field_id text NOT NULL,
    field_name text,
    lastseen timestamp without time zone NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    updated timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    total_visit integer DEFAULT 0,
    total_account integer DEFAULT 0,
    total_edit integer DEFAULT 0
);


ALTER TABLE public.event_field_audit OWNER TO endoguard;

--
-- Name: event_field_audit_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_field_audit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_field_audit_id_seq OWNER TO endoguard;

--
-- Name: event_field_audit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_field_audit_id_seq OWNED BY public.event_field_audit.id;


--
-- Name: event_field_audit_trail; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_field_audit_trail (
    id bigint NOT NULL,
    account_id bigint NOT NULL,
    key smallint NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    event_id bigint,
    field_name character varying,
    old_value character varying,
    new_value character varying,
    parent_id character varying,
    parent_name character varying,
    field_id bigint NOT NULL
);


ALTER TABLE public.event_field_audit_trail OWNER TO endoguard;

--
-- Name: event_field_audit_trail_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_field_audit_trail_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_field_audit_trail_id_seq OWNER TO endoguard;

--
-- Name: event_field_audit_trail_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_field_audit_trail_id_seq OWNED BY public.event_field_audit_trail.id;


--
-- Name: event_http_method; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_http_method (
    id smallint NOT NULL,
    value text NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.event_http_method OWNER TO endoguard;

--
-- Name: event_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_id_seq OWNER TO endoguard;

--
-- Name: event_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_id_seq OWNED BY public.event.id;


--
-- Name: event_incorrect; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_incorrect (
    id bigint NOT NULL,
    payload json NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    errors json,
    traceid character varying(36) DEFAULT NULL::character varying,
    key integer
);


ALTER TABLE public.event_incorrect OWNER TO endoguard;

--
-- Name: event_incorrect_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_incorrect_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_incorrect_id_seq OWNER TO endoguard;

--
-- Name: event_incorrect_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_incorrect_id_seq OWNED BY public.event_incorrect.id;


--
-- Name: event_ip; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_ip (
    id bigint NOT NULL,
    ip inet NOT NULL,
    key smallint NOT NULL,
    country smallint NOT NULL,
    cidr text,
    data_center boolean,
    tor boolean,
    vpn boolean,
    checked boolean DEFAULT false,
    relay boolean,
    lastseen timestamp without time zone DEFAULT '1970-01-01 00:00:00'::timestamp without time zone NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    updated timestamp without time zone NOT NULL,
    lastcheck timestamp without time zone,
    total_visit integer DEFAULT 0,
    blocklist boolean,
    isp bigint,
    shared smallint DEFAULT 0,
    domains_count json,
    fraud_detected boolean DEFAULT false NOT NULL,
    hash text,
    alert_list boolean,
    starlink boolean
);


ALTER TABLE public.event_ip OWNER TO endoguard;

--
-- Name: event_ip_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_ip_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_ip_id_seq OWNER TO endoguard;

--
-- Name: event_ip_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_ip_id_seq OWNED BY public.event_ip.id;


--
-- Name: event_isp; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_isp (
    id bigint NOT NULL,
    key smallint NOT NULL,
    asn integer NOT NULL,
    name text,
    description text,
    total_visit integer DEFAULT 0,
    total_account integer DEFAULT 0,
    lastseen timestamp without time zone,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated timestamp without time zone NOT NULL,
    total_ip integer DEFAULT 0
);


ALTER TABLE public.event_isp OWNER TO endoguard;

--
-- Name: event_isp_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_isp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_isp_id_seq OWNER TO endoguard;

--
-- Name: event_isp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_isp_id_seq OWNED BY public.event_isp.id;


--
-- Name: event_logbook; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_logbook (
    id bigint NOT NULL,
    ended timestamp(3) without time zone DEFAULT now() NOT NULL,
    key smallint NOT NULL,
    ip inet,
    event bigint,
    error_type smallint NOT NULL,
    error_text text,
    raw text,
    started timestamp(3) without time zone,
    endpoint text
);


ALTER TABLE public.event_logbook OWNER TO endoguard;

--
-- Name: event_logbook_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_logbook_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_logbook_id_seq OWNER TO endoguard;

--
-- Name: event_logbook_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_logbook_id_seq OWNED BY public.event_logbook.id;


--
-- Name: event_payload; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_payload (
    id bigint NOT NULL,
    key smallint NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    payload json
);


ALTER TABLE public.event_payload OWNER TO endoguard;

--
-- Name: event_payload_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_payload_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_payload_id_seq OWNER TO endoguard;

--
-- Name: event_payload_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_payload_id_seq OWNED BY public.event_payload.id;


--
-- Name: event_phone; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_phone (
    id bigint NOT NULL,
    account_id bigint NOT NULL,
    key integer NOT NULL,
    phone_number character varying(19) NOT NULL,
    calling_country_code integer,
    national_format character varying,
    country_code smallint,
    validation_errors json,
    mobile_country_code smallint,
    mobile_network_code smallint,
    carrier_name character varying(128) DEFAULT NULL::character varying,
    type character varying(32) DEFAULT NULL::character varying,
    lastseen timestamp without time zone NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated timestamp without time zone NOT NULL,
    checked boolean,
    shared smallint DEFAULT 0,
    fraud_detected boolean DEFAULT false NOT NULL,
    hash text,
    alert_list boolean,
    profiles integer,
    iso_country_code character varying(8),
    invalid boolean
);


ALTER TABLE public.event_phone OWNER TO endoguard;

--
-- Name: event_phone_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_phone_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_phone_id_seq OWNER TO endoguard;

--
-- Name: event_phone_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_phone_id_seq OWNED BY public.event_phone.id;


--
-- Name: event_referer; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_referer (
    id bigint NOT NULL,
    key smallint NOT NULL,
    referer text,
    lastseen timestamp without time zone NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.event_referer OWNER TO endoguard;

--
-- Name: event_referer_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_referer_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_referer_id_seq OWNER TO endoguard;

--
-- Name: event_referer_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_referer_id_seq OWNED BY public.event_referer.id;


--
-- Name: event_session; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_session (
    id bigint NOT NULL,
    key smallint NOT NULL,
    account_id bigint NOT NULL,
    total_visit integer DEFAULT 0,
    total_device integer DEFAULT 0,
    total_ip integer DEFAULT 0,
    total_country integer DEFAULT 0,
    lastseen timestamp(3) without time zone NOT NULL,
    created timestamp(3) without time zone NOT NULL,
    updated timestamp without time zone NOT NULL
);


ALTER TABLE public.event_session OWNER TO endoguard;

--
-- Name: event_session_stat; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_session_stat (
    id bigint NOT NULL,
    session_id bigint NOT NULL,
    key smallint NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    duration integer,
    ip_count integer,
    device_count integer,
    event_count integer,
    country_count integer,
    new_ip_count integer,
    new_device_count integer,
    http_codes jsonb DEFAULT '[]'::jsonb,
    http_methods jsonb DEFAULT '[]'::jsonb,
    event_types jsonb DEFAULT '[]'::jsonb,
    completed boolean DEFAULT false
);


ALTER TABLE public.event_session_stat OWNER TO endoguard;

--
-- Name: event_session_stat_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_session_stat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_session_stat_id_seq OWNER TO endoguard;

--
-- Name: event_session_stat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_session_stat_id_seq OWNED BY public.event_session_stat.id;


--
-- Name: event_type; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_type (
    id smallint NOT NULL,
    value text NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.event_type OWNER TO endoguard;

--
-- Name: event_ua_parsed; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_ua_parsed (
    id bigint NOT NULL,
    device text,
    browser_name text,
    browser_version text,
    os_name text,
    os_version text,
    ua text,
    uuid uuid,
    modified boolean,
    checked boolean DEFAULT false NOT NULL,
    key smallint NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.event_ua_parsed OWNER TO endoguard;

--
-- Name: event_ua_parsed_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_ua_parsed_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_ua_parsed_id_seq OWNER TO endoguard;

--
-- Name: event_ua_parsed_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_ua_parsed_id_seq OWNED BY public.event_ua_parsed.id;


--
-- Name: event_url; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_url (
    id bigint NOT NULL,
    key smallint NOT NULL,
    url text,
    lastseen timestamp without time zone DEFAULT '1970-01-01 00:00:00'::timestamp without time zone NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    updated timestamp without time zone NOT NULL,
    title text,
    total_visit integer DEFAULT 0,
    total_ip integer DEFAULT 0,
    total_device integer DEFAULT 0,
    total_account integer DEFAULT 0,
    total_country integer DEFAULT 0,
    http_code smallint,
    total_edit integer DEFAULT 0
);


ALTER TABLE public.event_url OWNER TO endoguard;

--
-- Name: event_url_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_url_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_url_id_seq OWNER TO endoguard;

--
-- Name: event_url_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_url_id_seq OWNED BY public.event_url.id;


--
-- Name: event_url_query; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.event_url_query (
    id bigint DEFAULT nextval('public.event_url_id_seq'::regclass) NOT NULL,
    key smallint NOT NULL,
    url integer NOT NULL,
    query text,
    lastseen timestamp without time zone NOT NULL,
    created timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.event_url_query OWNER TO endoguard;

--
-- Name: event_url_query_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.event_url_query_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.event_url_query_id_seq OWNER TO endoguard;

--
-- Name: event_url_query_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.event_url_query_id_seq OWNED BY public.event_url_query.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.migrations (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp without time zone NOT NULL
);


ALTER TABLE public.migrations OWNER TO endoguard;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.migrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO endoguard;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: queue_account_operation; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.queue_account_operation (
    id bigint NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    updated timestamp without time zone DEFAULT now() NOT NULL,
    event_account bigint,
    key smallint NOT NULL,
    action public.queue_account_operation_action NOT NULL,
    status public.queue_account_operation_status DEFAULT 'waiting'::public.queue_account_operation_status NOT NULL
);


ALTER TABLE public.queue_account_operation OWNER TO endoguard;

--
-- Name: queue_account_operation_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.queue_account_operation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.queue_account_operation_id_seq OWNER TO endoguard;

--
-- Name: queue_account_operation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: endoguard
--

ALTER SEQUENCE public.queue_account_operation_id_seq OWNED BY public.queue_account_operation.id;


--
-- Name: queue_new_events_cursor; Type: TABLE; Schema: public; Owner: endoguard
--

CREATE TABLE public.queue_new_events_cursor (
    last_event_id bigint NOT NULL,
    locked boolean NOT NULL,
    updated timestamp without time zone DEFAULT now()
);


ALTER TABLE public.queue_new_events_cursor OWNER TO endoguard;

--
-- Name: session_id_seq; Type: SEQUENCE; Schema: public; Owner: endoguard
--

CREATE SEQUENCE public.session_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.session_id_seq OWNER TO endoguard;

--
-- Name: dshb_api id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_api ALTER COLUMN id SET DEFAULT nextval('public.dshb_api_id_seq'::regclass);


--
-- Name: dshb_logs id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_logs ALTER COLUMN id SET DEFAULT nextval('public.dshb_logs_id_seq'::regclass);


--
-- Name: dshb_manual_check_history id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_manual_check_history ALTER COLUMN id SET DEFAULT nextval('public.dshb_manual_check_history_id_seq'::regclass);


--
-- Name: dshb_message id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_message ALTER COLUMN id SET DEFAULT nextval('public.dshb_message_id_seq'::regclass);


--
-- Name: dshb_operators id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators ALTER COLUMN id SET DEFAULT nextval('public.dshb_operators_id_seq'::regclass);


--
-- Name: dshb_operators_forgot_password id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators_forgot_password ALTER COLUMN id SET DEFAULT nextval('public.dshb_operators_forgot_password_id_seq'::regclass);


--
-- Name: dshb_operators_rules id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators_rules ALTER COLUMN id SET DEFAULT nextval('public.dshb_operators_rules_id_seq'::regclass);


--
-- Name: dshb_updates id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_updates ALTER COLUMN id SET DEFAULT nextval('public.dshb_updates_id_seq'::regclass);


--
-- Name: event id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event ALTER COLUMN id SET DEFAULT nextval('public.event_id_seq'::regclass);


--
-- Name: event_account id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_account ALTER COLUMN id SET DEFAULT nextval('public.event_account_id_seq'::regclass);


--
-- Name: event_country id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_country ALTER COLUMN id SET DEFAULT nextval('public.event_country_id_seq'::regclass);


--
-- Name: event_device id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_device ALTER COLUMN id SET DEFAULT nextval('public.event_device_id_seq'::regclass);


--
-- Name: event_domain id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_domain ALTER COLUMN id SET DEFAULT nextval('public.event_domain_id_seq'::regclass);


--
-- Name: event_email id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_email ALTER COLUMN id SET DEFAULT nextval('public.event_email_id_seq'::regclass);


--
-- Name: event_field_audit id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_field_audit ALTER COLUMN id SET DEFAULT nextval('public.event_field_audit_id_seq'::regclass);


--
-- Name: event_field_audit_trail id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_field_audit_trail ALTER COLUMN id SET DEFAULT nextval('public.event_field_audit_trail_id_seq'::regclass);


--
-- Name: event_incorrect id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_incorrect ALTER COLUMN id SET DEFAULT nextval('public.event_incorrect_id_seq'::regclass);


--
-- Name: event_ip id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ip ALTER COLUMN id SET DEFAULT nextval('public.event_ip_id_seq'::regclass);


--
-- Name: event_isp id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_isp ALTER COLUMN id SET DEFAULT nextval('public.event_isp_id_seq'::regclass);


--
-- Name: event_logbook id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_logbook ALTER COLUMN id SET DEFAULT nextval('public.event_logbook_id_seq'::regclass);


--
-- Name: event_payload id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_payload ALTER COLUMN id SET DEFAULT nextval('public.event_payload_id_seq'::regclass);


--
-- Name: event_phone id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_phone ALTER COLUMN id SET DEFAULT nextval('public.event_phone_id_seq'::regclass);


--
-- Name: event_referer id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_referer ALTER COLUMN id SET DEFAULT nextval('public.event_referer_id_seq'::regclass);


--
-- Name: event_session_stat id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_session_stat ALTER COLUMN id SET DEFAULT nextval('public.event_session_stat_id_seq'::regclass);


--
-- Name: event_ua_parsed id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ua_parsed ALTER COLUMN id SET DEFAULT nextval('public.event_ua_parsed_id_seq'::regclass);


--
-- Name: event_url id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_url ALTER COLUMN id SET DEFAULT nextval('public.event_url_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: queue_account_operation id; Type: DEFAULT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.queue_account_operation ALTER COLUMN id SET DEFAULT nextval('public.queue_account_operation_id_seq'::regclass);


--
-- Data for Name: countries; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.countries (iso, value, id) FROM stdin;
AD	Andorra	6
AE	United Arab Emirates	236
AF	Afghanistan	1
AG	Antigua & Barbuda	10
AI	Anguilla	8
AL	Albania	3
AM	Armenia	12
AN	Netherlands Antilles	252
AO	Angola	7
AP	Asia/Pacific Region	250
AQ	Antarctica	9
AR	Argentina	11
AS	American Samoa	5
AT	Austria	15
AU	Australia	14
AW	Aruba	13
AX	??land Islands	2
AZ	Azerbaijan	16
BA	Bosnia & Herzegovina	28
BB	Barbados	20
BD	Bangladesh	19
BE	Belgium	22
BF	Burkina Faso	36
BG	Bulgaria	35
BH	Bahrain	18
BI	Burundi	37
BJ	Benin	24
BL	St. Barth??lemy	205
BM	Bermuda	25
BN	Brunei	34
BO	Bolivia	27
BQ	Caribbean Netherlands	42
BR	Brazil	31
BS	Bahamas	17
BT	Bhutan	26
BV	Bouvet Island	30
BW	Botswana	29
BY	Belarus	21
BZ	Belize	23
CA	Canada	40
CC	Cocos (Keeling) Islands	49
CD	Congo - Kinshasa	53
CF	Central African Republic	44
CG	Congo - Brazzaville	52
CH	Switzerland	216
CI	C??te d???Ivoire	56
CK	Cook Islands	54
CL	Chile	46
CM	Cameroon	39
CN	China	47
CO	Colombia	50
CR	Costa Rica	55
CS	Czechoslovakia	253
CU	Cuba	58
CV	Cape Verde	41
CW	Cura??ao	59
CX	Christmas Island	48
CY	Cyprus	60
CZ	Czechia	61
DE	Germany	85
DJ	Djibouti	63
DK	Denmark	62
DM	Dominica	64
DO	Dominican Republic	65
DZ	Algeria	4
EC	Ecuador	66
EE	Estonia	71
EG	Egypt	67
EH	Western Sahara	246
ER	Eritrea	70
ES	Spain	203
ET	Ethiopia	73
EU	European Union	251
FI	Finland	77
FJ	Fiji	76
FK	Falkland Islands	74
FM	Micronesia	143
FO	Faroe Islands	75
FR	France	78
GA	Gabon	82
GB	Great Britain	237
GD	Grenada	90
GE	Georgia	84
GF	French Guiana	79
GG	Guernsey	94
GH	Ghana	86
GI	Gibraltar	87
GL	Greenland	89
GM	Gambia	83
GN	Guinea	95
GP	Guadeloupe	91
GQ	Equatorial Guinea	69
GR	Greece	88
GS	South Georgia & South Sandwich Islands	200
GT	Guatemala	93
GU	Guam	92
GW	Guinea-Bissau	96
GY	Guyana	97
HK	Hong Kong SAR China	101
HM	Heard & McDonald Islands	99
HN	Honduras	100
HR	Croatia	57
HT	Haiti	98
HU	Hungary	102
ID	Indonesia	105
IE	Ireland	108
IL	Israel	110
IM	Isle of Man	109
IN	India	104
IO	British Indian Ocean Territory	32
IQ	Iraq	107
IR	Iran	106
IS	Iceland	103
IT	Italy	111
JE	Jersey	114
JM	Jamaica	112
JO	Jordan	115
JP	Japan	113
KE	Kenya	117
KG	Kyrgyzstan	120
KH	Cambodia	38
KI	Kiribati	118
KM	Comoros	51
KN	St. Kitts & Nevis	207
KP	North Korea	163
KR	South Korea	201
KW	Kuwait	119
KY	Cayman Islands	43
KZ	Kazakhstan	116
LA	Laos	121
LB	Lebanon	123
LC	St. Lucia	208
LI	Liechtenstein	127
LK	Sri Lanka	204
LR	Liberia	125
LS	Lesotho	124
LT	Lithuania	128
LU	Luxembourg	129
LV	Latvia	122
LY	Libya	126
MA	Morocco	149
MC	Monaco	145
MD	Moldova	144
ME	Montenegro	147
MF	St. Martin	209
MG	Madagascar	131
MH	Marshall Islands	137
MK	North Macedonia	164
ML	Mali	135
MM	Myanmar (Burma)	151
MN	Mongolia	146
MO	Macao SAR China	130
MP	Northern Mariana Islands	165
MQ	Martinique	138
MR	Mauritania	139
MS	Montserrat	148
MT	Malta	136
MU	Mauritius	140
MV	Maldives	134
MW	Malawi	132
MX	Mexico	142
MY	Malaysia	133
MZ	Mozambique	150
N/A	Not Available	0
NA	Namibia	152
NC	New Caledonia	156
NE	Niger	159
NF	Norfolk Island	162
NG	Nigeria	160
NI	Nicaragua	158
NL	Netherlands	155
NO	Norway	166
NP	Nepal	154
NR	Nauru	153
NU	Niue	161
NZ	New Zealand	157
OM	Oman	167
PA	Panama	171
PE	Peru	174
PF	French Polynesia	80
PG	Papua New Guinea	172
PH	Philippines	175
PK	Pakistan	168
PL	Poland	177
PM	St. Pierre & Miquelon	210
PN	Pitcairn Islands	176
PR	Puerto Rico	179
PS	Palestinian Territories	170
PT	Portugal	178
PW	Palau	169
PY	Paraguay	173
QA	Qatar	180
RE	R??union	181
RO	Romania	182
RS	Serbia	190
RU	Russia	183
RW	Rwanda	184
SA	Saudi Arabia	188
SB	Solomon Islands	197
SC	Seychelles	191
SD	Sudan	212
SE	Sweden	215
SG	Singapore	193
SH	St. Helena	206
SI	Slovenia	196
SJ	Svalbard & Jan Mayen	214
SK	Slovakia	195
SL	Sierra Leone	192
SM	San Marino	186
SN	Senegal	189
SO	Somalia	198
SR	Suriname	213
SS	South Sudan	202
ST	S??o Tom?? & Pr??ncipe	187
SV	El Salvador	68
SX	Sint Maarten	194
SY	Syria	217
SZ	Eswatini	72
TC	Turks & Caicos Islands	230
TD	Chad	45
TF	French Southern Territories	81
TG	Togo	223
TH	Thailand	221
TJ	Tajikistan	219
TK	Tokelau	224
TL	Timor-Leste	222
TM	Turkmenistan	229
TN	Tunisia	227
TO	Tonga	225
TR	Turkey	228
TT	Trinidad & Tobago	226
TV	Tuvalu	231
TW	Taiwan	218
TZ	Tanzania	220
UA	Ukraine	235
UG	Uganda	234
UK	United Kingdom	255
UM	U.S. Outlying Islands	232
US	United States	238
UY	Uruguay	239
UZ	Uzbekistan	240
VA	Vatican City	242
VC	St. Vincent & Grenadines	211
VE	Venezuela	243
VG	British Virgin Islands	33
VI	U.S. Virgin Islands	233
VN	Vietnam	244
VU	Vanuatu	241
WF	Wallis & Futuna	245
WS	Samoa	185
YE	Yemen	247
YT	Mayotte	141
YU	Yugoslavia	254
ZA	South Africa	199
ZM	Zambia	248
ZW	Zimbabwe	249
\.


--
-- Data for Name: dshb_api; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_api (id, key, quote, creator, created_at, skip_enriching_attributes, retention_policy, skip_blacklist_sync, token, last_call_reached, blacklist_threshold, review_queue_threshold) FROM stdin;
1	5447170e162f449ac31d7fb951e0dc40	100	1	2026-04-15 16:22:37.536844	["ip", "email", "domain", "phone"]	0	t	\N	\N	-1	33
\.


--
-- Data for Name: dshb_api_co_owners; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_api_co_owners (operator, api, created_at) FROM stdin;
\.


--
-- Data for Name: dshb_logs; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_logs (id, text, created_at) FROM stdin;
1	{"ip":"172.22.0.1","code":500,"message":"ERROR_500, syntax error, unexpected token \\"\\\\\\" [\\/var\\/www\\/html\\/tmp\\/1rhmuqyegwrps.3qca86rz1fmsw.php:4]","trace":"[tmp\\/1rhmuqyegwrps.22hj5ksiabvos.php:8] Preview->render()<br>[tmp\\/1rhmuqyegwrps.3pmp24rvp5kw8.php:1] Preview->render()<br>[app\\/Views\\/Frontend.php:36] Preview->render()<br>[index.php:102] Base->run()\\n","date":"Wednesday 15th of April 2026 04:23:20 PM","post":[],"get":[],"sql_log":"(0.9ms) CREATE TABLE IF NOT EXISTS \\"dshb_sessions\\" (\\n\\t\\"session_id\\" VARCHAR(255),\\n\\t\\"data\\" TEXT,\\n\\t\\"ip\\" VARCHAR(45),\\n\\t\\"agent\\" VARCHAR(300),\\n\\t\\"stamp\\" INTEGER,\\n\\tPRIMARY KEY (\\"session_id\\")\\n);\\n(36.9ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_sessions' AND C.TABLE_CATALOG='endoguard'\\n(2.4ms) SELECT \\"session_id\\",\\"data\\",\\"ip\\",\\"agent\\",\\"stamp\\" FROM \\"dshb_sessions\\" WHERE session_id='0955bb1b98282b3ac3366f63ec7464da'\\n(18.0ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(2.1ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(18.2ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(1.5ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(16.8ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(16.0ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(15.1ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_updates' AND C.TABLE_CATALOG='endoguard'\\n(2.4ms) SELECT 1 FROM information_schema.tables WHERE table_name = 'dshb_updates'\\n(1.7ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.5' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.7ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.6' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(1.3ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.7' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.8ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.8' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.9ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.9' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.9ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.10' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.8ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.11' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.7ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.12' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(19.4ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='event_logbook' AND C.TABLE_CATALOG='endoguard'\\n(3.0ms) SELECT\\r\\n                event_logbook.event,\\r\\n                event_logbook.ended     AS lastseen\\r\\n\\r\\n            FROM\\r\\n                event_logbook\\r\\n\\r\\n            WHERE\\r\\n                event_logbook.key = 1 AND\\r\\n                (\\r\\n                    event_logbook.error_type = 0  OR\\r\\n                    event_logbook.error_type = 1\\r\\n                ) AND\\r\\n                event_logbook.endpoint = '\\/sensor\\/'\\r\\n            ORDER BY event_logbook.ended DESC\\r\\n            LIMIT 1\\n(16.4ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.5ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(13.4ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_message' AND C.TABLE_CATALOG='endoguard'\\n(1.4ms) SELECT\\r\\n                id,\\r\\n                text,\\r\\n                title,\\r\\n                created_at\\r\\n            FROM\\r\\n                dshb_message\\r\\n            ORDER BY id DESC\\r\\n            LIMIT 1\\n(17.6ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(15.3ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.7ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(15.2ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='queue_account_operation' AND C.TABLE_CATALOG='endoguard'\\n(4.4ms) SELECT\\r\\n                1\\r\\n            FROM\\r\\n                queue_account_operation\\r\\n            WHERE\\r\\n                action = 'enrichment'::queue_account_operation_action AND\\r\\n                status != 'completed'::queue_account_operation_status AND\\r\\n                status != 'failed'::queue_account_operation_status AND\\r\\n                key = 1\\r\\n            ORDER BY updated DESC\\r\\n            LIMIT 1\\n(16.3ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE\\r\\n                dshb_api.creator = 1\\r\\n            ORDER BY id ASC\\n(16.7ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(17.0ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.5ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(15.5ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.4ms) SELECT\\r\\n                dshb_api.skip_enriching_attributes\\r\\n            FROM dshb_api\\r\\n            WHERE\\r\\n                dshb_api.id = 1\\n(16.4ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(14.8ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.5ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n"}	2026-04-15 16:23:20.946535+00
2	{"ip":"172.22.0.1","code":500,"message":"ERROR_500, syntax error, unexpected token \\"\\\\\\" [\\/var\\/www\\/html\\/tmp\\/1rhmuqyegwrps.3qca86rz1fmsw.php:4]","trace":"[tmp\\/1rhmuqyegwrps.22hj5ksiabvos.php:8] Preview->render()<br>[tmp\\/1rhmuqyegwrps.3pmp24rvp5kw8.php:1] Preview->render()<br>[app\\/Views\\/Frontend.php:36] Preview->render()<br>[index.php:102] Base->run()\\n","date":"Wednesday 15th of April 2026 04:23:50 PM","post":[],"get":[],"sql_log":"(1.1ms) CREATE TABLE IF NOT EXISTS \\"dshb_sessions\\" (\\n\\t\\"session_id\\" VARCHAR(255),\\n\\t\\"data\\" TEXT,\\n\\t\\"ip\\" VARCHAR(45),\\n\\t\\"agent\\" VARCHAR(300),\\n\\t\\"stamp\\" INTEGER,\\n\\tPRIMARY KEY (\\"session_id\\")\\n);\\n(37.2ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_sessions' AND C.TABLE_CATALOG='endoguard'\\n(3.0ms) SELECT \\"session_id\\",\\"data\\",\\"ip\\",\\"agent\\",\\"stamp\\" FROM \\"dshb_sessions\\" WHERE session_id='0955bb1b98282b3ac3366f63ec7464da'\\n(19.7ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(2.0ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(15.8ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(1.4ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(18.1ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(15.7ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.7ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(16.5ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_updates' AND C.TABLE_CATALOG='endoguard'\\n(2.6ms) SELECT 1 FROM information_schema.tables WHERE table_name = 'dshb_updates'\\n(1.7ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.5' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.9ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.6' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.7ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.7' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.8ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.8' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.9ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.9' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.8ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.10' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(1.0ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.11' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(0.9ms) SELECT 1 FROM dshb_updates WHERE version = 'v0.9.12' AND (service = 'core' OR service = :service || '_processing') LIMIT 1\\n(15.5ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='event_logbook' AND C.TABLE_CATALOG='endoguard'\\n(3.4ms) SELECT\\r\\n                event_logbook.event,\\r\\n                event_logbook.ended     AS lastseen\\r\\n\\r\\n            FROM\\r\\n                event_logbook\\r\\n\\r\\n            WHERE\\r\\n                event_logbook.key = 1 AND\\r\\n                (\\r\\n                    event_logbook.error_type = 0  OR\\r\\n                    event_logbook.error_type = 1\\r\\n                ) AND\\r\\n                event_logbook.endpoint = '\\/sensor\\/'\\r\\n            ORDER BY event_logbook.ended DESC\\r\\n            LIMIT 1\\n(16.8ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(13.0ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_message' AND C.TABLE_CATALOG='endoguard'\\n(1.5ms) SELECT\\r\\n                id,\\r\\n                text,\\r\\n                title,\\r\\n                created_at\\r\\n            FROM\\r\\n                dshb_message\\r\\n            ORDER BY id DESC\\r\\n            LIMIT 1\\n(17.9ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(16.1ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.7ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(32.1ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='queue_account_operation' AND C.TABLE_CATALOG='endoguard'\\n(8.0ms) SELECT\\r\\n                1\\r\\n            FROM\\r\\n                queue_account_operation\\r\\n            WHERE\\r\\n                action = 'enrichment'::queue_account_operation_action AND\\r\\n                status != 'completed'::queue_account_operation_status AND\\r\\n                status != 'failed'::queue_account_operation_status AND\\r\\n                key = 1\\r\\n            ORDER BY updated DESC\\r\\n            LIMIT 1\\n(28.6ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(1.1ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE\\r\\n                dshb_api.creator = 1\\r\\n            ORDER BY id ASC\\n(26.6ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(0.7ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(18.4ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n(18.0ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.6ms) SELECT\\r\\n                dshb_api.skip_enriching_attributes\\r\\n            FROM dshb_api\\r\\n            WHERE\\r\\n                dshb_api.id = 1\\n(20.7ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_operators' AND C.TABLE_CATALOG='endoguard'\\n(0.8ms) SELECT\\r\\n                id,\\r\\n                email,\\r\\n                password,\\r\\n                firstname,\\r\\n                lastname,\\r\\n                activation_key,\\r\\n                timezone,\\r\\n                review_queue_cnt,\\r\\n                review_queue_updated_at,\\r\\n                unreviewed_items_reminder_freq,\\r\\n                last_event_time,\\r\\n                blacklist_users_cnt\\r\\n            FROM\\r\\n                dshb_operators\\r\\n            WHERE\\r\\n                dshb_operators.id = 1 AND\\r\\n                dshb_operators.is_closed = 0\\n(16.8ms) SELECT C.COLUMN_NAME AS field,C.DATA_TYPE AS type,C.COLUMN_DEFAULT AS defval,C.IS_NULLABLE AS nullable,COALESCE(POSITION('nextval' IN C.COLUMN_DEFAULT),0) AS autoinc,T.CONSTRAINT_TYPE AS pkey FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT OUTER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ON C.TABLE_NAME=K.TABLE_NAME AND C.COLUMN_NAME=K.COLUMN_NAME AND C.TABLE_SCHEMA=K.TABLE_SCHEMA AND C.TABLE_CATALOG=K.TABLE_CATALOG LEFT OUTER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON K.TABLE_NAME=T.TABLE_NAME AND K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND K.TABLE_SCHEMA=T.TABLE_SCHEMA AND K.TABLE_CATALOG=T.TABLE_CATALOG WHERE C.TABLE_NAME='dshb_api' AND C.TABLE_CATALOG='endoguard'\\n(0.7ms) SELECT\\r\\n                id,\\r\\n                key,\\r\\n                token,\\r\\n                creator,\\r\\n                created_at,\\r\\n                retention_policy,\\r\\n                last_call_reached,\\r\\n                skip_blacklist_sync,\\r\\n                review_queue_threshold,\\r\\n                skip_enriching_attributes,\\r\\n                blacklist_threshold\\r\\n            FROM\\r\\n                dshb_api\\r\\n            WHERE dshb_api.id = 1\\n"}	2026-04-15 16:23:50.705934+00
\.


--
-- Data for Name: dshb_manual_check_history; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_manual_check_history (id, operator, type, search_query, created_at) FROM stdin;
\.


--
-- Data for Name: dshb_message; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_message (id, text, title, created_at) FROM stdin;
\.


--
-- Data for Name: dshb_operators; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_operators (id, email, password, firstname, lastname, city, state, zip, country, company, vat, is_active, activation_key, created_at, street, is_closed, timezone, review_queue_cnt, review_queue_updated_at, last_event_time, unreviewed_items_reminder_freq, last_unreviewed_items_reminder, blacklist_users_cnt) FROM stdin;
1	admin@gmail.com	$2y$10$2C20CKhvNU8jKecJHZvIX.VVfH1LjPqPK870bcP/kjIBnu8BwCUbK	\N	\N	\N	\N	\N	\N	\N	\N	1	\N	2026-04-15 16:22:37.500388+00	\N	0	UTC	0	2026-04-17 10:29:22.951801	2026-04-15 18:20:10.555	weekly	\N	1
\.


--
-- Data for Name: dshb_operators_forgot_password; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_operators_forgot_password (id, operator_id, renew_key, status, created_at) FROM stdin;
\.


--
-- Data for Name: dshb_operators_rules; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_operators_rules (id, value, created_at, key, proportion, proportion_updated_at, rule_uid) FROM stdin;
1	0	2026-04-15 16:22:37.603868+00	1	\N	\N	I01
2	0	2026-04-15 16:22:37.607938+00	1	\N	\N	I06
3	0	2026-04-15 16:22:37.610005+00	1	\N	\N	E01
4	0	2026-04-15 16:22:37.612526+00	1	\N	\N	B07
5	0	2026-04-15 16:22:37.614744+00	1	\N	\N	E17
6	0	2026-04-15 16:22:37.61669+00	1	\N	\N	E02
7	0	2026-04-15 16:22:37.619405+00	1	\N	\N	E12
8	0	2026-04-15 16:22:37.621885+00	1	\N	\N	E16
9	0	2026-04-15 16:22:37.625145+00	1	\N	\N	E19
10	0	2026-04-15 16:22:37.627264+00	1	\N	\N	D06
11	0	2026-04-15 16:22:37.629687+00	1	\N	\N	B01
12	0	2026-04-15 16:22:37.632334+00	1	\N	\N	I09
13	0	2026-04-15 16:22:37.634795+00	1	\N	\N	E11
14	0	2026-04-15 16:22:37.637512+00	1	\N	\N	E13
15	0	2026-04-15 16:22:37.639687+00	1	\N	\N	A01
16	0	2026-04-15 16:22:37.641873+00	1	\N	\N	B02
17	0	2026-04-15 16:22:37.644148+00	1	\N	\N	B03
18	0	2026-04-15 16:22:37.645926+00	1	\N	\N	B04
19	0	2026-04-15 16:22:37.648282+00	1	\N	\N	B05
20	0	2026-04-15 16:22:37.650758+00	1	\N	\N	C11
21	0	2026-04-15 16:22:37.652808+00	1	\N	\N	B06
22	0	2026-04-15 16:22:37.655439+00	1	\N	\N	I08
23	0	2026-04-15 16:22:37.657752+00	1	\N	\N	B19
24	0	2026-04-15 16:22:37.660386+00	1	\N	\N	D07
25	0	2026-04-15 16:22:37.662681+00	1	\N	\N	D04
26	0	2026-04-15 16:22:37.664688+00	1	\N	\N	D05
27	0	2026-04-15 16:22:37.667016+00	1	\N	\N	I10
28	0	2026-04-15 16:22:37.669302+00	1	\N	\N	E20
29	0	2026-04-15 16:22:37.671419+00	1	\N	\N	E21
30	0	2026-04-15 16:22:37.674171+00	1	\N	\N	E22
31	0	2026-04-15 16:22:37.676194+00	1	\N	\N	C13
32	0	2026-04-15 16:22:37.678765+00	1	\N	\N	C14
33	0	2026-04-15 16:22:37.681257+00	1	\N	\N	C15
34	0	2026-04-15 16:22:37.683201+00	1	\N	\N	C16
35	0	2026-04-15 16:22:37.685541+00	1	\N	\N	C12
36	0	2026-04-15 16:22:37.687628+00	1	\N	\N	E29
37	0	2026-04-15 16:22:37.690122+00	1	\N	\N	E30
38	0	2026-04-15 16:22:37.692655+00	1	\N	\N	D10
39	0	2026-04-15 16:22:37.694611+00	1	\N	\N	B18
40	0	2026-04-15 16:22:37.69707+00	1	\N	\N	B20
41	0	2026-04-15 16:22:37.699243+00	1	\N	\N	B17
42	0	2026-04-15 16:22:37.701197+00	1	\N	\N	I11
43	0	2026-04-15 16:22:37.703836+00	1	\N	\N	E27
44	0	2026-04-15 16:22:37.705939+00	1	\N	\N	E28
45	0	2026-04-15 16:22:37.708574+00	1	\N	\N	C01
46	0	2026-04-15 16:22:37.711009+00	1	\N	\N	C02
47	0	2026-04-15 16:22:37.712892+00	1	\N	\N	P04
48	0	2026-04-15 16:22:37.715388+00	1	\N	\N	C03
49	0	2026-04-15 16:22:37.717833+00	1	\N	\N	C04
50	0	2026-04-15 16:22:37.71996+00	1	\N	\N	C05
51	0	2026-04-15 16:22:37.722553+00	1	\N	\N	C06
52	0	2026-04-15 16:22:37.724724+00	1	\N	\N	C07
53	0	2026-04-15 16:22:37.727367+00	1	\N	\N	C08
54	0	2026-04-15 16:22:37.729455+00	1	\N	\N	C09
55	0	2026-04-15 16:22:37.731438+00	1	\N	\N	C10
56	0	2026-04-15 16:22:37.734148+00	1	\N	\N	I03
57	0	2026-04-15 16:22:37.736468+00	1	\N	\N	B21
58	0	2026-04-15 16:22:37.739135+00	1	\N	\N	I05
59	0	2026-04-15 16:22:37.741246+00	1	\N	\N	I02
60	0	2026-04-15 16:22:37.743834+00	1	\N	\N	I07
61	0	2026-04-15 16:22:37.747022+00	1	\N	\N	D01
62	0	2026-04-15 16:22:37.74934+00	1	\N	\N	D02
63	0	2026-04-15 16:22:37.751749+00	1	\N	\N	I04
64	0	2026-04-15 16:22:37.754345+00	1	\N	\N	D03
65	0	2026-04-15 16:22:37.757565+00	1	\N	\N	A02
66	0	2026-04-15 16:22:37.759793+00	1	\N	\N	A03
67	0	2026-04-15 16:22:37.761846+00	1	\N	\N	A04
68	0	2026-04-15 16:22:37.764449+00	1	\N	\N	A05
69	0	2026-04-15 16:22:37.766494+00	1	\N	\N	A06
70	0	2026-04-15 16:22:37.76948+00	1	\N	\N	A07
71	0	2026-04-15 16:22:37.771866+00	1	\N	\N	A08
72	0	2026-04-15 16:22:37.774426+00	1	\N	\N	E23
73	0	2026-04-15 16:22:37.776978+00	1	\N	\N	E24
74	0	2026-04-15 16:22:37.779085+00	1	\N	\N	E25
75	0	2026-04-15 16:22:37.781696+00	1	\N	\N	B11
76	0	2026-04-15 16:22:37.784171+00	1	\N	\N	B12
77	0	2026-04-15 16:22:37.786794+00	1	\N	\N	D09
78	0	2026-04-15 16:22:37.789689+00	1	\N	\N	E26
79	0	2026-04-15 16:22:37.792348+00	1	\N	\N	B14
80	0	2026-04-15 16:22:37.795034+00	1	\N	\N	D08
81	0	2026-04-15 16:22:37.797109+00	1	\N	\N	E10
82	0	2026-04-15 16:22:37.79961+00	1	\N	\N	E14
83	0	2026-04-15 16:22:37.801988+00	1	\N	\N	P02
84	0	2026-04-15 16:22:37.804656+00	1	\N	\N	P01
85	0	2026-04-15 16:22:37.807045+00	1	\N	\N	E15
86	0	2026-04-15 16:22:37.808877+00	1	\N	\N	B08
87	0	2026-04-15 16:22:37.811476+00	1	\N	\N	B09
88	0	2026-04-15 16:22:37.814168+00	1	\N	\N	B10
89	0	2026-04-15 16:22:37.816656+00	1	\N	\N	B15
90	0	2026-04-15 16:22:37.819045+00	1	\N	\N	B16
91	0	2026-04-15 16:22:37.820856+00	1	\N	\N	B13
92	0	2026-04-15 16:22:37.823397+00	1	\N	\N	P03
93	0	2026-04-15 16:22:37.825787+00	1	\N	\N	E03
94	0	2026-04-15 16:22:37.827962+00	1	\N	\N	E04
95	0	2026-04-15 16:22:37.8304+00	1	\N	\N	E05
96	0	2026-04-15 16:22:37.832456+00	1	\N	\N	E06
97	0	2026-04-15 16:22:37.83501+00	1	\N	\N	E07
98	0	2026-04-15 16:22:37.837567+00	1	\N	\N	E08
99	0	2026-04-15 16:22:37.839582+00	1	\N	\N	E09
100	0	2026-04-15 16:22:37.842566+00	1	\N	\N	R01
101	0	2026-04-15 16:22:37.844779+00	1	\N	\N	R02
102	0	2026-04-15 16:22:37.847449+00	1	\N	\N	R03
103	0	2026-04-15 16:22:37.849608+00	1	\N	\N	B22
104	0	2026-04-15 16:22:37.852231+00	1	\N	\N	I12
105	0	2026-04-15 16:22:37.854716+00	1	\N	\N	B23
106	0	2026-04-15 16:22:37.856797+00	1	\N	\N	B24
107	0	2026-04-15 16:22:37.859486+00	1	\N	\N	B25
108	0	2026-04-15 16:22:37.861663+00	1	\N	\N	B26
\.


--
-- Data for Name: dshb_rules; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_rules (validated, uid, name, descr, attributes, updated, missing) FROM stdin;
t	I01	IP belongs to TOR	IP address is assigned to The Onion Router network. Very few people use TOR, mainly used for anonymization and accessing censored resources.	["ip"]	2026-04-15 16:06:52.431785	\N
t	I06	IP belongs to datacenter	The user is utilizing an ISP datacenter, which highly suggests the use of a VPN, script, or privacy software.	["ip"]	2026-04-15 16:06:52.431785	\N
t	E01	Invalid email format	Invalid email format. Should be 'username@domain.com'.	[]	2026-04-15 16:06:52.431785	\N
t	B07	User's full name contains digits	Full name contains digits, which is a rare behaviour for regular users.	[]	2026-04-15 16:06:52.431785	\N
t	E17	Free email and spam	Email appears in spam lists and registered by free provider. Increased risk of spamming.	["email", "domain"]	2026-04-15 16:06:52.431785	\N
t	E02	New domain and no breaches	Email belongs to recently created domain name and it doesn't appear in data breaches. Increased risk due to lack of authenticity.	["email", "domain"]	2026-04-15 16:06:52.431785	\N
t	E12	Free email and no breaches	Email belongs to free provider and it doesn't appear in data breaches. It may be a sign of a throwaway mailbox.	["email"]	2026-04-15 16:06:52.431785	\N
t	E16	Domain appears in spam lists	Email appears in spam lists, so the user may have spammed before.	["domain"]	2026-04-15 16:06:52.431785	\N
t	E19	Multiple emails changed	User has changed their email.	[]	2026-04-15 16:06:52.431785	\N
t	D06	Multiple devices per user	User accesses the account using multiple devices. Account may be used by different people.	[]	2026-04-15 16:06:52.431785	\N
t	B01	Multiple countries	IP addresses are located in diverse countries, which is a rare behaviour for regular users.	["ip"]	2026-04-15 16:06:52.431785	\N
t	I09	Numerous IPs	User accesses the account with numerous IP addresses. This behaviour occurs in less than one percent of desktop users.	[]	2026-04-15 16:06:52.431785	\N
t	E11	Disposable email	Disposable email addresses are temporary email addresses that users can create and use for a short period. They might use create fake accounts.	["email"]	2026-04-15 16:06:52.431785	\N
t	E13	New domain	Domain name was registered recently, which is rare for average users.	["domain"]	2026-04-15 16:06:52.431785	\N
t	A01	Multiple login fail	User failed to login multiple times in a short term, which can be a sign of account takeover.	[]	2026-04-15 16:06:52.431785	\N
t	B02	User has changed a password	The user has changed their password.	[]	2026-04-15 16:06:52.431785	\N
t	B03	User has changed an email	The user has changed their email.	[]	2026-04-15 16:06:52.431785	\N
t	B04	Multiple 5xx errors	The user made multiple requests which evoked internal server error.	[]	2026-04-15 16:06:52.431785	\N
t	B05	Multiple 4xx errors	The user made multiple requests which cannot be fulfilled.	[]	2026-04-15 16:06:52.431785	\N
t	C11	Russia IP address	IP address located in Russia. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	B06	Potentially vulnerable URL	The user made a request to suspicious URL.	[]	2026-04-15 16:06:52.431785	\N
t	I08	IP belongs to Starlink	IP address belongs to SpaceX satellite network.	["ip"]	2026-04-15 16:06:52.431785	\N
t	B19	Night time requests	User was active from midnight till 5 a. m.	[]	2026-04-15 16:06:52.431785	\N
t	D07	Several desktop devices	User accesses the account using different OS desktop devices. Account may be shared between different people.	[]	2026-04-15 16:06:52.431785	\N
t	D04	Rare browser device	User operates device with uncommon browser.	[]	2026-04-15 16:06:52.431785	\N
t	D05	Rare OS device	User operates device with uncommon OS.	[]	2026-04-15 16:06:52.431785	\N
t	I10	Only residential IPs	User uses only residential IP addresses.	["ip"]	2026-04-15 16:06:52.431785	\N
t	E20	Established domain (> 3 year old)	Email belongs to long-established domain name registered at least 3 years ago.	["domain"]	2026-04-15 16:06:52.431785	\N
t	E21	No vowels in email	Email username does not contain any vowels.	[]	2026-04-15 16:06:52.431785	\N
t	E22	No consonants in email	Email username does not contain any consonants.	[]	2026-04-15 16:06:52.431785	\N
t	C13	North America IP address	IP address located in Canada or USA.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C14	Australia IP address	IP address located in Australia.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C15	UAE IP address	IP address located in United Arab Emirates.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C16	Japan IP address	IP address located in Japan.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C12	European IP address	IP address located in Europe Union.	["ip"]	2026-04-15 16:06:52.431785	\N
t	E29	Old breach (>3 years)	The earliest data breach associated with the email appeared more than 3 years ago. Can be used as sign of aged email.	["email"]	2026-04-15 16:06:52.431785	\N
t	E30	Domain with average rank	Email domain has tranco rank between 100.000 and 4.000.000	["domain"]	2026-04-15 16:06:52.431785	\N
t	D10	Potentially vulnerable User-Agent	The user made a request with potentially vulnerable User-Agent.	[]	2026-04-15 16:06:52.431785	\N
t	B18	HEAD request	HTTP request HEAD method is oftenly used by bots.	[]	2026-04-15 16:06:52.431785	\N
t	B20	Multiple countries in one session	User's country was changed in less than 30 minutes.	["ip"]	2026-04-15 16:06:52.431785	\N
t	B17	Single country	IP addresses are located in a single country.	["ip"]	2026-04-15 16:06:52.431785	\N
t	I11	Single network	IP addresses belong to one network.	["ip"]	2026-04-15 16:06:52.431785	\N
t	E27	Email breaches	Email appears in data breaches.	["email"]	2026-04-15 16:06:52.431785	\N
t	E28	No digits in email	The email address does not include digits.	[]	2026-04-15 16:06:52.431785	\N
t	C01	Nigeria IP address	IP address located in Nigeria. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C02	India IP address	IP address located in India. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	P04	Valid phone	User provided correct phone number.	["phone"]	2026-04-15 16:06:52.431785	\N
t	C03	China IP address	IP address located in China. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C04	Brazil IP address	IP address located in Brazil. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C05	Pakistan IP address	IP address located in Pakistan. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C06	Indonesia IP address	IP address located in Indonesia. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C07	Venezuela IP address	IP address located in Venezuela. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C08	South Africa IP address	IP address located in South Africa. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C09	Philippines IP address	IP address located in Philippines. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	C10	Romania IP address	IP address located in Romania. This region is associated with a higher risk.	["ip"]	2026-04-15 16:06:52.431785	\N
t	I03	IP appears in spam list	User may have exhibited unwanted activity before at other web services.	["ip"]	2026-04-15 16:06:52.431785	\N
t	B21	Multiple devices in one session	User's device was changed in less than 30 minutes.	[]	2026-04-15 16:06:52.431785	\N
t	I05	IP belongs to commercial VPN	User tries to hide their real location or bypass regional blocking.	["ip"]	2026-04-15 16:06:52.431785	\N
t	I02	IP hosting domain	Higher risk of crawler bot. Such IP addresses are used only for hosting and are not provided to regular users by ISP.	["ip"]	2026-04-15 16:06:52.431785	\N
t	I07	IP belongs to Apple Relay	IP address belongs to iCloud Private Relay, part of an iCloud+ subscription.	["ip"]	2026-04-15 16:06:52.431785	\N
t	D01	Device is unknown	User has manipulated the device information, so it is not recognized.	[]	2026-04-15 16:06:52.431785	\N
t	D02	Device is Linux	Linux OS is not used by avarage users, increased risk of crawler bot.	[]	2026-04-15 16:06:52.431785	\N
t	I04	Shared IP	Multiple users detected on the same IP address. High risk of multi-accounting.	[]	2026-04-15 16:06:52.431785	\N
t	D03	Device is bot	The user may be using a device with a user agent that is identified as a bot.	[]	2026-04-15 16:06:52.431785	\N
t	A02	Login failed on new device	User failed to login with new device, which can be a sign of account takeover.	[]	2026-04-15 16:06:52.431785	\N
t	A03	New device and new country	User logged in with new device from new location, which can be a sign of account takeover.	["ip"]	2026-04-15 16:06:52.431785	\N
t	A04	New device and new subnet	User logged in with new device from new subnet, which can be a sign of account takeover.	["ip"]	2026-04-15 16:06:52.431785	\N
t	A05	Password change on new device	User changed their password on new device, which can be a sign of account takeover.	[]	2026-04-15 16:06:52.431785	\N
t	A06	Password change in new country	User changed their password in new country, which can be a sign of account takeover.	["ip"]	2026-04-15 16:06:52.431785	\N
t	A07	Password change in new subnet	User changed their password in new subnet, which can be a sign of account takeover.	["ip"]	2026-04-15 16:06:52.431785	\N
t	A08	Browser language changed	User accessed the account with new browser language, which can be a sign of account takeover.	[]	2026-04-15 16:06:52.431785	\N
t	E23	Educational domain (.edu)	Email belongs to educational domain.	[]	2026-04-15 16:06:52.431785	\N
t	E24	Government domain (.gov)	Email belongs to government domain.	[]	2026-04-15 16:06:52.431785	\N
t	E25	Military domain (.mil)	Email belongs to military domain.	[]	2026-04-15 16:06:52.431785	\N
t	B11	New account (1 day)	The account has been created today.	[]	2026-04-15 16:06:52.431785	\N
t	B12	New account (1 week)	The account has been created this week.	[]	2026-04-15 16:06:52.431785	\N
t	D09	Old browser	User accesses the account using an old versioned browser.	[]	2026-04-15 16:06:52.431785	\N
t	E26	iCloud mailbox	Email belongs to Apple domains icloud.com, me.com or mac.com.	[]	2026-04-15 16:06:52.431785	\N
t	B14	Aged account (>30 days)	The account has been created over 30 days ago.	[]	2026-04-15 16:06:52.431785	\N
t	D08	Two or more phone devices	User accesses the account using numerous phone devices, which is not standard behaviour for regular users. Account may be shared between different people.	[]	2026-04-15 16:06:52.431785	\N
t	E10	The website is unavailable	Domain's website seems to be inactive, which could be a sign of fake mailbox.	["domain"]	2026-04-15 16:06:52.431785	\N
t	E14	No MX record	Email's domain name has no MX record, so domain is not able to have any mailboxes. It is a sign of fake mailbox.	["domain"]	2026-04-15 16:06:52.431785	\N
t	P02	Phone country mismatch	Phone number country is not among the countries from which user has logged in. May be a sign of invalid phone number.	["phone"]	2026-04-15 16:06:52.431785	\N
t	P01	Invalid phone format	User provided incorrect phone number.	["phone"]	2026-04-15 16:06:52.431785	\N
t	E15	No breaches for email	The email was not involved in any data breaches, which could suggest it's a newly created or less frequently used mailbox.	["email"]	2026-04-15 16:06:52.431785	\N
t	B08	Dormant account (30 days)	The account has been inactive for 30 days.	[]	2026-04-15 16:06:52.431785	\N
t	B09	Dormant account (90 days)	The account has been inactive for 90 days.	[]	2026-04-15 16:06:52.431785	\N
t	B10	Dormant account (1 year)	The account has been inactive for a year.	[]	2026-04-15 16:06:52.431785	\N
t	B15	Aged account (>90 days)	The account has been created over 90 days ago.	[]	2026-04-15 16:06:52.431785	\N
t	B16	Aged account (>180 days)	The account has been created over 180 days ago.	[]	2026-04-15 16:06:52.431785	\N
t	B13	New account (1 month)	The account has been created this month.	[]	2026-04-15 16:06:52.431785	\N
t	P03	Shared phone number	User provided a phone number shared with another user.	[]	2026-04-15 16:06:52.431785	\N
t	E03	Suspicious words in email	Email contains word parts that usually found in automatically generated mailboxes.	[]	2026-04-15 16:06:52.431785	\N
t	E04	Numeric email name	The email's username consists entirely of numbers, which is uncommon for typical email addresses.	[]	2026-04-15 16:06:52.431785	\N
t	E05	Special characters in email	The email address features an unusually high number of special characters, which is atypical for standard email addresses.	[]	2026-04-15 16:06:52.431785	\N
t	E06	Consecutive digits in email	The email address includes at least two consecutive digits, which is a characteristic sometimes associated with temporary or fake email accounts.	[]	2026-04-15 16:06:52.431785	\N
t	E07	Long email username	The email's username exceeds the average length, which could suggest it's a temporary email address.	[]	2026-04-15 16:06:52.431785	\N
t	E08	Long domain name	Email's domain name is too long. Long domain names are cheaply registered and rarely used for email addresses by regular users.	[]	2026-04-15 16:06:52.431785	\N
t	E09	Free email provider	Email belongs to free provider. These mailboxes are the easiest to create.	["domain"]	2026-04-15 16:06:52.431785	\N
t	R01	IP in blacklist	This IP address appears in the blacklist.	[]	2026-04-15 16:06:52.431785	\N
t	R02	Email in blacklist	This email address appears in the blacklist.	[]	2026-04-15 16:06:52.431785	\N
t	R03	Phone in blacklist	 This phone number appears in the blacklist.	[]	2026-04-15 16:06:52.431785	\N
t	B22	Multiple IP addresses in one session	User's IP address was changed in less than 30 minutes.	[]	2026-04-15 16:06:52.431785	\N
t	I12	IP belongs to LAN	IP address belongs to local access network.	[]	2026-04-15 16:06:52.431785	\N
t	B23	User's full name contains space or hyphen	Full name contains space or hyphen, which is a rare behaviour for regular users.	[]	2026-04-15 16:06:52.431785	\N
t	B24	Empty referer	The user made a request without a referer.	[]	2026-04-15 16:06:52.431785	\N
t	B25	Unauthorized request	The user made a successful request without authorization.	[]	2026-04-15 16:06:53.80585	\N
t	B26	Single event sessions	User had sessions with only one event.	[]	2026-04-15 16:06:53.809436	\N
\.


--
-- Data for Name: dshb_sessions; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_sessions (session_id, data, ip, agent, stamp) FROM stdin;
c0cc538a0b3b7974a7ab043f7d8ed1b3	csrf|s:32:"4ad76c0c52b6a78c7b0ac56428beb36e";active_user_id|i:1;active_key_id|i:1;filterEndDate|N;filterStartDate|N;	172.22.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36	1776423686
\.


--
-- Data for Name: dshb_updates; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.dshb_updates (id, service, version, created) FROM stdin;
1	core	v0.9.5	2026-04-15 16:06:52.412738
2	core	v0.9.6	2026-04-15 16:06:52.431785
3	core	v0.9.7	2026-04-15 16:06:53.202372
4	core	v0.9.8	2026-04-15 16:06:53.254913
5	core	v0.9.9	2026-04-15 16:06:53.337014
6	core	v0.9.10	2026-04-15 16:06:53.398338
7	core	v0.9.11	2026-04-15 16:06:53.523255
8	core	v0.9.12	2026-04-15 16:06:53.566012
\.


--
-- Data for Name: event; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event (id, key, account, ip, url, device, "time", query, traceid, referer, type, email, phone, http_code, http_method, session_id, payload) FROM stdin;
293	1	23	56	21	13	2026-04-11 02:24:55	\N	\N	\N	8	\N	\N	404	1	71639462	\N
294	1	26	53	25	16	2026-04-14 11:49:55	\N	\N	\N	13	\N	\N	403	9	61994075	\N
295	1	24	44	22	14	2026-04-10 09:40:55	\N	\N	\N	6	\N	\N	403	9	10974375	\N
296	1	23	42	21	13	2026-04-16 11:52:55	\N	\N	\N	7	\N	\N	200	6	71639462	\N
297	1	23	41	22	13	2026-04-14 21:13:55	\N	\N	\N	11	\N	\N	200	10	71639462	\N
298	1	23	59	26	13	2026-04-09 19:07:55	\N	\N	\N	11	\N	\N	404	6	71639462	\N
299	1	25	50	22	15	2026-04-10 09:00:55	\N	\N	\N	12	\N	\N	200	10	67298695	\N
300	1	27	58	22	17	2026-04-13 11:45:55	\N	\N	\N	12	\N	\N	403	9	38251372	\N
301	1	23	47	21	13	2026-04-12 20:24:55	\N	\N	\N	3	\N	\N	500	2	71639462	\N
302	1	26	59	21	16	2026-04-12 17:07:55	\N	\N	\N	3	\N	\N	200	11	61994075	\N
303	1	26	47	22	16	2026-04-11 16:12:55	\N	\N	\N	5	\N	\N	200	8	61994075	\N
304	1	27	51	26	17	2026-04-10 11:05:55	\N	\N	\N	5	\N	\N	200	5	38251372	\N
305	1	27	53	25	17	2026-04-15 18:57:55	\N	\N	\N	9	\N	\N	200	5	38251372	\N
306	1	24	43	22	14	2026-04-12 22:42:55	\N	\N	\N	13	\N	\N	500	11	10974375	\N
307	1	26	59	25	16	2026-04-15 17:54:55	\N	\N	\N	5	\N	\N	302	10	61994075	\N
308	1	24	43	23	14	2026-04-10 02:37:55	\N	\N	\N	12	\N	\N	200	2	10974375	\N
309	1	24	53	26	14	2026-04-16 04:54:55	\N	\N	\N	1	\N	\N	200	11	10974375	\N
310	1	27	55	22	17	2026-04-16 01:39:55	\N	\N	\N	6	\N	\N	302	4	38251372	\N
311	1	26	50	22	16	2026-04-13 09:29:55	\N	\N	\N	2	\N	\N	500	9	61994075	\N
312	1	25	59	24	15	2026-04-10 17:16:55	\N	\N	\N	11	\N	\N	200	1	67298695	\N
313	1	24	48	24	14	2026-04-16 00:42:55	\N	\N	\N	12	\N	\N	200	3	10974375	\N
314	1	23	46	22	13	2026-04-15 23:33:55	\N	\N	\N	2	\N	\N	500	9	71639462	\N
315	1	24	59	26	14	2026-04-10 14:14:55	\N	\N	\N	7	\N	\N	500	7	10974375	\N
316	1	27	47	22	17	2026-04-16 03:42:55	\N	\N	\N	4	\N	\N	200	8	38251372	\N
317	1	27	60	21	17	2026-04-10 11:51:55	\N	\N	\N	2	\N	\N	200	8	38251372	\N
318	1	23	43	22	13	2026-04-16 00:14:55	\N	\N	\N	6	\N	\N	200	3	71639462	\N
319	1	26	53	23	16	2026-04-12 17:32:55	\N	\N	\N	9	\N	\N	200	2	61994075	\N
320	1	26	55	21	16	2026-04-11 01:10:55	\N	\N	\N	3	\N	\N	200	3	61994075	\N
321	1	26	58	24	16	2026-04-09 20:07:55	\N	\N	\N	4	\N	\N	200	3	61994075	\N
322	1	24	45	25	14	2026-04-12 05:37:55	\N	\N	\N	9	\N	\N	200	3	10974375	\N
323	1	26	52	25	16	2026-04-14 10:21:55	\N	\N	\N	13	\N	\N	200	4	61994075	\N
324	1	26	58	22	16	2026-04-14 15:27:55	\N	\N	\N	13	\N	\N	403	10	61994075	\N
325	1	23	44	22	13	2026-04-14 04:49:55	\N	\N	\N	5	\N	\N	404	11	71639462	\N
326	1	27	57	22	17	2026-04-14 20:05:55	\N	\N	\N	3	\N	\N	200	4	38251372	\N
327	1	24	41	21	14	2026-04-11 07:48:55	\N	\N	\N	5	\N	\N	404	11	10974375	\N
328	1	23	58	24	13	2026-04-12 21:41:55	\N	\N	\N	6	\N	\N	404	11	71639462	\N
329	1	26	44	24	16	2026-04-10 01:01:55	\N	\N	\N	6	\N	\N	200	10	61994075	\N
330	1	27	46	23	17	2026-04-10 01:18:55	\N	\N	\N	6	\N	\N	200	4	38251372	\N
331	1	26	56	25	16	2026-04-15 08:38:55	\N	\N	\N	12	\N	\N	500	10	61994075	\N
332	1	25	44	23	15	2026-04-15 01:03:55	\N	\N	\N	4	\N	\N	200	6	67298695	\N
333	1	23	43	23	13	2026-04-15 04:58:55	\N	\N	\N	8	\N	\N	403	8	71639462	\N
334	1	24	42	25	14	2026-04-10 23:54:55	\N	\N	\N	5	\N	\N	403	8	10974375	\N
335	1	24	58	26	14	2026-04-10 16:01:55	\N	\N	\N	11	\N	\N	200	2	10974375	\N
336	1	26	48	21	16	2026-04-12 05:34:55	\N	\N	\N	4	\N	\N	404	7	61994075	\N
337	1	25	47	25	15	2026-04-12 00:49:55	\N	\N	\N	2	\N	\N	404	7	67298695	\N
338	1	23	51	21	13	2026-04-11 10:00:55	\N	\N	\N	12	\N	\N	200	1	71639462	\N
339	1	25	60	24	15	2026-04-11 17:01:55	\N	\N	\N	5	\N	\N	403	1	67298695	\N
340	1	25	60	22	15	2026-04-15 14:09:55	\N	\N	\N	11	\N	\N	200	11	67298695	\N
341	1	26	41	22	16	2026-04-15 23:57:55	\N	\N	\N	9	\N	\N	403	11	61994075	\N
342	1	24	42	24	14	2026-04-10 17:27:55	\N	\N	\N	1	\N	\N	500	2	10974375	\N
343	1	26	46	25	16	2026-04-14 16:29:55	\N	\N	\N	6	\N	\N	200	5	61994075	\N
344	1	27	47	22	17	2026-04-14 21:50:55	\N	\N	\N	9	\N	\N	302	9	38251372	\N
345	1	24	49	21	14	2026-04-10 07:41:55	\N	\N	\N	6	\N	\N	200	2	10974375	\N
346	1	27	42	22	17	2026-04-11 03:16:55	\N	\N	\N	2	\N	\N	200	11	38251372	\N
347	1	25	43	26	15	2026-04-13 18:40:55	\N	\N	\N	9	\N	\N	404	5	67298695	\N
348	1	27	47	25	17	2026-04-10 18:23:55	\N	\N	\N	10	\N	\N	403	4	38251372	\N
349	1	27	53	25	17	2026-04-15 17:46:55	\N	\N	\N	12	\N	\N	500	7	38251372	\N
350	1	23	56	23	13	2026-04-16 02:54:55	\N	\N	\N	6	\N	\N	200	3	71639462	\N
351	1	24	59	26	14	2026-04-10 11:27:55	\N	\N	\N	13	\N	\N	200	9	10974375	\N
352	1	27	54	26	17	2026-04-12 23:23:55	\N	\N	\N	10	\N	\N	200	1	38251372	\N
353	1	26	60	23	16	2026-04-09 22:29:55	\N	\N	\N	6	\N	\N	200	2	61994075	\N
354	1	24	47	21	14	2026-04-15 14:05:55	\N	\N	\N	1	\N	\N	200	9	10974375	\N
355	1	24	57	22	14	2026-04-13 04:52:55	\N	\N	\N	7	\N	\N	200	7	10974375	\N
356	1	27	54	22	17	2026-04-10 15:03:55	\N	\N	\N	3	\N	\N	200	8	38251372	\N
357	1	23	54	24	13	2026-04-10 01:10:55	\N	\N	\N	12	\N	\N	500	4	71639462	\N
358	1	25	42	24	15	2026-04-12 21:37:55	\N	\N	\N	12	\N	\N	403	4	67298695	\N
359	1	26	56	21	16	2026-04-09 22:16:55	\N	\N	\N	8	\N	\N	200	5	61994075	\N
360	1	24	45	26	14	2026-04-13 16:15:55	\N	\N	\N	11	\N	\N	403	2	10974375	\N
361	1	25	58	24	15	2026-04-10 08:30:55	\N	\N	\N	5	\N	\N	500	1	67298695	\N
362	1	24	52	23	14	2026-04-14 04:24:55	\N	\N	\N	4	\N	\N	500	3	10974375	\N
363	1	24	58	22	14	2026-04-09 20:34:55	\N	\N	\N	10	\N	\N	500	3	10974375	\N
364	1	25	43	21	15	2026-04-14 11:30:55	\N	\N	\N	13	\N	\N	500	10	67298695	\N
365	1	26	52	21	16	2026-04-11 23:42:55	\N	\N	\N	7	\N	\N	200	4	61994075	\N
366	1	23	55	23	13	2026-04-16 12:01:55	\N	\N	\N	1	\N	\N	200	11	71639462	\N
367	1	25	50	22	15	2026-04-12 04:17:55	\N	\N	\N	10	\N	\N	200	11	67298695	\N
368	1	25	55	22	15	2026-04-14 06:06:55	\N	\N	\N	6	\N	\N	200	6	67298695	\N
369	1	24	54	26	14	2026-04-16 13:33:55	\N	\N	\N	7	\N	\N	404	3	10974375	\N
370	1	25	45	23	15	2026-04-14 17:56:55	\N	\N	\N	10	\N	\N	403	5	67298695	\N
371	1	27	47	22	17	2026-04-12 00:29:55	\N	\N	\N	13	\N	\N	500	4	38251372	\N
372	1	27	59	25	17	2026-04-12 21:48:55	\N	\N	\N	5	\N	\N	200	5	38251372	\N
373	1	25	55	25	15	2026-04-12 20:43:55	\N	\N	\N	4	\N	\N	500	5	67298695	\N
374	1	25	47	26	15	2026-04-14 03:02:55	\N	\N	\N	1	\N	\N	404	1	67298695	\N
375	1	23	48	21	13	2026-04-15 17:25:55	\N	\N	\N	1	\N	\N	200	3	71639462	\N
376	1	24	54	26	14	2026-04-15 07:40:55	\N	\N	\N	2	\N	\N	500	1	10974375	\N
377	1	23	55	26	13	2026-04-14 01:18:55	\N	\N	\N	8	\N	\N	500	5	71639462	\N
378	1	26	56	24	16	2026-04-14 10:32:55	\N	\N	\N	7	\N	\N	302	7	61994075	\N
379	1	27	44	26	17	2026-04-12 14:25:55	\N	\N	\N	4	\N	\N	200	1	38251372	\N
380	1	23	43	23	13	2026-04-15 03:32:55	\N	\N	\N	10	\N	\N	403	7	71639462	\N
381	1	26	43	22	16	2026-04-16 12:58:55	\N	\N	\N	6	\N	\N	403	10	61994075	\N
382	1	27	41	24	17	2026-04-16 16:01:55	\N	\N	\N	13	\N	\N	302	7	38251372	\N
383	1	27	48	22	17	2026-04-14 18:03:55	\N	\N	\N	10	\N	\N	200	9	38251372	\N
384	1	25	56	26	15	2026-04-12 23:59:55	\N	\N	\N	3	\N	\N	200	8	67298695	\N
385	1	27	51	22	17	2026-04-15 01:27:55	\N	\N	\N	7	\N	\N	500	11	38251372	\N
386	1	23	54	24	13	2026-04-13 04:00:55	\N	\N	\N	1	\N	\N	200	10	71639462	\N
387	1	26	50	26	16	2026-04-12 05:59:55	\N	\N	\N	8	\N	\N	404	3	61994075	\N
388	1	24	49	26	14	2026-04-12 01:23:55	\N	\N	\N	2	\N	\N	403	2	10974375	\N
389	1	27	54	22	17	2026-04-12 01:27:55	\N	\N	\N	7	\N	\N	403	1	38251372	\N
390	1	23	47	26	13	2026-04-15 00:01:55	\N	\N	\N	11	\N	\N	200	10	71639462	\N
391	1	25	55	21	15	2026-04-14 16:51:55	\N	\N	\N	12	\N	\N	200	9	67298695	\N
392	1	26	48	21	16	2026-04-12 00:50:55	\N	\N	\N	9	\N	\N	500	5	61994075	\N
393	1	26	59	25	16	2026-04-15 11:36:55	\N	\N	\N	13	\N	\N	200	7	61994075	\N
394	1	25	51	23	15	2026-04-16 08:02:55	\N	\N	\N	6	\N	\N	500	10	67298695	\N
395	1	25	49	26	15	2026-04-12 03:06:55	\N	\N	\N	1	\N	\N	200	9	67298695	\N
396	1	25	51	21	15	2026-04-15 13:36:55	\N	\N	\N	6	\N	\N	500	6	67298695	\N
397	1	26	50	23	16	2026-04-11 05:11:55	\N	\N	\N	11	\N	\N	200	4	61994075	\N
398	1	24	49	25	14	2026-04-16 15:53:55	\N	\N	\N	9	\N	\N	302	3	10974375	\N
399	1	24	46	22	14	2026-04-12 11:37:55	\N	\N	\N	8	\N	\N	200	7	10974375	\N
400	1	25	48	26	15	2026-04-13 12:19:55	\N	\N	\N	6	\N	\N	404	3	67298695	\N
401	1	26	53	25	16	2026-04-13 19:16:55	\N	\N	\N	8	\N	\N	404	7	61994075	\N
402	1	23	55	22	13	2026-04-12 13:44:55	\N	\N	\N	9	\N	\N	200	1	71639462	\N
403	1	23	44	26	13	2026-04-12 20:11:55	\N	\N	\N	7	\N	\N	200	11	71639462	\N
404	1	26	59	26	16	2026-04-15 17:44:55	\N	\N	\N	8	\N	\N	500	11	61994075	\N
405	1	25	42	25	15	2026-04-11 04:22:55	\N	\N	\N	2	\N	\N	302	2	67298695	\N
406	1	27	47	23	17	2026-04-15 18:33:55	\N	\N	\N	3	\N	\N	404	4	38251372	\N
407	1	24	43	24	14	2026-04-13 12:01:55	\N	\N	\N	13	\N	\N	500	3	10974375	\N
408	1	25	48	26	15	2026-04-12 05:03:55	\N	\N	\N	10	\N	\N	500	11	67298695	\N
409	1	27	49	24	17	2026-04-10 18:04:55	\N	\N	\N	6	\N	\N	200	5	38251372	\N
410	1	24	42	24	14	2026-04-13 11:00:55	\N	\N	\N	1	\N	\N	403	4	10974375	\N
411	1	27	52	22	17	2026-04-12 02:26:55	\N	\N	\N	6	\N	\N	404	9	38251372	\N
412	1	23	41	26	13	2026-04-13 08:27:55	\N	\N	\N	5	\N	\N	200	1	71639462	\N
413	1	25	47	26	15	2026-04-13 06:21:55	\N	\N	\N	10	\N	\N	200	9	67298695	\N
414	1	27	52	21	17	2026-04-16 15:42:55	\N	\N	\N	13	\N	\N	200	3	38251372	\N
415	1	24	42	25	14	2026-04-14 05:45:55	\N	\N	\N	5	\N	\N	200	7	10974375	\N
416	1	26	42	22	16	2026-04-10 18:23:55	\N	\N	\N	11	\N	\N	403	4	61994075	\N
417	1	24	49	21	14	2026-04-10 05:53:55	\N	\N	\N	2	\N	\N	404	4	10974375	\N
418	1	23	51	21	13	2026-04-15 21:58:55	\N	\N	\N	13	\N	\N	404	3	71639462	\N
419	1	26	50	24	16	2026-04-14 03:59:55	\N	\N	\N	11	\N	\N	200	4	61994075	\N
420	1	24	56	26	14	2026-04-13 05:41:55	\N	\N	\N	11	\N	\N	200	5	10974375	\N
421	1	24	43	21	14	2026-04-09 19:36:55	\N	\N	\N	10	\N	\N	200	8	10974375	\N
422	1	27	56	21	17	2026-04-14 11:19:55	\N	\N	\N	3	\N	\N	403	7	38251372	\N
423	1	25	51	25	15	2026-04-14 12:33:55	\N	\N	\N	4	\N	\N	200	5	67298695	\N
424	1	25	47	26	15	2026-04-10 20:46:55	\N	\N	\N	11	\N	\N	200	1	67298695	\N
425	1	24	45	22	14	2026-04-10 23:45:55	\N	\N	\N	6	\N	\N	200	7	10974375	\N
426	1	26	57	25	16	2026-04-12 12:24:55	\N	\N	\N	11	\N	\N	403	7	61994075	\N
427	1	23	52	23	13	2026-04-14 23:58:55	\N	\N	\N	1	\N	\N	200	3	71639462	\N
428	1	25	41	21	15	2026-04-14 23:07:55	\N	\N	\N	11	\N	\N	200	1	67298695	\N
429	1	27	43	25	17	2026-04-15 22:52:55	\N	\N	\N	6	\N	\N	500	7	38251372	\N
430	1	24	43	23	14	2026-04-12 06:29:55	\N	\N	\N	11	\N	\N	403	6	10974375	\N
431	1	24	51	26	14	2026-04-11 07:25:55	\N	\N	\N	2	\N	\N	200	7	10974375	\N
432	1	25	57	21	15	2026-04-12 04:02:55	\N	\N	\N	2	\N	\N	200	7	67298695	\N
433	1	23	57	24	13	2026-04-12 11:05:55	\N	\N	\N	1	\N	\N	200	6	71639462	\N
434	1	23	48	25	13	2026-04-12 20:45:55	\N	\N	\N	2	\N	\N	200	9	71639462	\N
435	1	23	52	21	13	2026-04-12 03:10:55	\N	\N	\N	2	\N	\N	200	11	71639462	\N
436	1	23	53	26	13	2026-04-13 15:19:55	\N	\N	\N	4	\N	\N	500	6	71639462	\N
437	1	27	46	26	17	2026-04-11 18:24:55	\N	\N	\N	7	\N	\N	200	3	38251372	\N
438	1	27	48	25	17	2026-04-12 02:35:55	\N	\N	\N	10	\N	\N	200	8	38251372	\N
439	1	25	50	25	15	2026-04-13 16:44:55	\N	\N	\N	4	\N	\N	200	1	67298695	\N
440	1	26	53	26	16	2026-04-13 07:24:55	\N	\N	\N	5	\N	\N	200	3	61994075	\N
441	1	24	56	21	14	2026-04-13 12:06:55	\N	\N	\N	1	\N	\N	200	7	10974375	\N
442	1	23	52	25	13	2026-04-12 12:03:55	\N	\N	\N	2	\N	\N	200	3	71639462	\N
443	1	23	52	21	13	2026-04-12 17:03:55	\N	\N	\N	13	\N	\N	200	8	71639462	\N
444	1	24	47	25	14	2026-04-11 03:50:55	\N	\N	\N	10	\N	\N	500	5	10974375	\N
445	1	23	43	26	13	2026-04-14 00:04:55	\N	\N	\N	6	\N	\N	403	4	71639462	\N
446	1	27	45	21	17	2026-04-10 12:19:55	\N	\N	\N	6	\N	\N	200	6	38251372	\N
447	1	24	47	23	14	2026-04-12 15:53:55	\N	\N	\N	9	\N	\N	500	2	10974375	\N
448	1	26	52	21	16	2026-04-13 20:15:55	\N	\N	\N	13	\N	\N	200	5	61994075	\N
449	1	24	41	24	14	2026-04-15 21:37:55	\N	\N	\N	13	\N	\N	403	10	10974375	\N
450	1	23	57	26	13	2026-04-11 00:39:55	\N	\N	\N	3	\N	\N	302	11	71639462	\N
451	1	25	55	24	15	2026-04-13 19:07:55	\N	\N	\N	8	\N	\N	403	9	67298695	\N
452	1	27	47	24	17	2026-04-12 03:41:55	\N	\N	\N	1	\N	\N	200	8	38251372	\N
453	1	25	56	25	15	2026-04-16 08:06:55	\N	\N	\N	11	\N	\N	200	7	67298695	\N
454	1	24	51	22	14	2026-04-14 08:50:55	\N	\N	\N	10	\N	\N	200	8	10974375	\N
455	1	25	42	26	15	2026-04-14 07:44:55	\N	\N	\N	4	\N	\N	500	8	67298695	\N
456	1	24	46	24	14	2026-04-12 10:15:55	\N	\N	\N	4	\N	\N	200	7	10974375	\N
457	1	26	55	24	16	2026-04-16 04:08:55	\N	\N	\N	1	\N	\N	200	2	61994075	\N
458	1	27	58	21	17	2026-04-11 05:11:55	\N	\N	\N	3	\N	\N	500	8	38251372	\N
459	1	25	46	26	15	2026-04-14 05:53:55	\N	\N	\N	10	\N	\N	200	3	67298695	\N
460	1	23	53	26	13	2026-04-10 00:37:55	\N	\N	\N	9	\N	\N	200	8	71639462	\N
461	1	23	57	26	13	2026-04-14 16:41:55	\N	\N	\N	11	\N	\N	200	3	71639462	\N
462	1	27	54	21	17	2026-04-16 06:29:55	\N	\N	\N	8	\N	\N	302	11	38251372	\N
463	1	25	51	23	15	2026-04-16 11:11:55	\N	\N	\N	4	\N	\N	404	9	67298695	\N
464	1	27	59	25	17	2026-04-14 17:03:55	\N	\N	\N	1	\N	\N	200	8	38251372	\N
465	1	25	41	24	15	2026-04-11 08:41:55	\N	\N	\N	8	\N	\N	403	6	67298695	\N
466	1	26	49	23	16	2026-04-11 13:58:55	\N	\N	\N	9	\N	\N	200	8	61994075	\N
467	1	24	42	22	14	2026-04-11 03:33:55	\N	\N	\N	5	\N	\N	404	5	10974375	\N
468	1	25	42	25	15	2026-04-14 22:24:55	\N	\N	\N	11	\N	\N	200	7	67298695	\N
469	1	23	50	25	13	2026-04-13 19:10:55	\N	\N	\N	13	\N	\N	302	2	71639462	\N
470	1	24	49	26	14	2026-04-14 03:12:55	\N	\N	\N	5	\N	\N	500	8	10974375	\N
471	1	27	52	22	17	2026-04-15 18:39:55	\N	\N	\N	6	\N	\N	200	5	38251372	\N
472	1	23	48	22	13	2026-04-14 19:00:55	\N	\N	\N	6	\N	\N	200	2	71639462	\N
473	1	24	47	23	14	2026-04-15 00:42:55	\N	\N	\N	10	\N	\N	200	2	10974375	\N
474	1	26	48	24	16	2026-04-10 03:04:55	\N	\N	\N	8	\N	\N	200	11	61994075	\N
475	1	27	49	25	17	2026-04-14 22:43:55	\N	\N	\N	9	\N	\N	200	10	38251372	\N
476	1	23	54	26	13	2026-04-13 06:13:55	\N	\N	\N	8	\N	\N	200	2	71639462	\N
477	1	27	44	25	17	2026-04-14 00:18:55	\N	\N	\N	2	\N	\N	302	1	38251372	\N
478	1	24	49	25	14	2026-04-10 05:04:55	\N	\N	\N	5	\N	\N	200	3	10974375	\N
479	1	26	50	25	16	2026-04-15 10:26:55	\N	\N	\N	10	\N	\N	200	11	61994075	\N
480	1	25	55	22	15	2026-04-16 12:19:55	\N	\N	\N	5	\N	\N	302	3	67298695	\N
481	1	24	55	26	14	2026-04-13 15:46:55	\N	\N	\N	6	\N	\N	200	10	10974375	\N
482	1	27	55	22	17	2026-04-11 22:02:55	\N	\N	\N	3	\N	\N	200	2	38251372	\N
483	1	25	42	22	15	2026-04-13 07:43:55	\N	\N	\N	9	\N	\N	403	6	67298695	\N
484	1	23	49	25	13	2026-04-12 20:57:55	\N	\N	\N	5	\N	\N	200	5	71639462	\N
485	1	25	53	23	15	2026-04-10 19:01:55	\N	\N	\N	9	\N	\N	200	2	67298695	\N
486	1	26	59	25	16	2026-04-15 07:41:55	\N	\N	\N	2	\N	\N	200	7	61994075	\N
487	1	27	58	26	17	2026-04-11 15:36:55	\N	\N	\N	6	\N	\N	500	8	38251372	\N
488	1	26	47	26	16	2026-04-10 08:31:55	\N	\N	\N	2	\N	\N	302	3	61994075	\N
489	1	26	58	21	16	2026-04-13 09:20:55	\N	\N	\N	1	\N	\N	500	11	61994075	\N
490	1	23	55	22	13	2026-04-11 17:20:55	\N	\N	\N	1	\N	\N	404	9	71639462	\N
491	1	26	46	25	16	2026-04-14 15:19:55	\N	\N	\N	7	\N	\N	200	8	61994075	\N
492	1	27	46	25	17	2026-04-11 05:58:55	\N	\N	\N	9	\N	\N	302	3	38251372	\N
\.


--
-- Data for Name: event_account; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_account (id, userid, created, key, lastip, lastseen, fullname, is_important, firstname, middlename, lastname, total_visit, total_country, total_ip, total_device, score_updated_at, score, score_details, lastemail, lastphone, total_shared_ip, total_shared_phone, reviewed, fraud, latest_decision, score_recalculate, session_id, updated, added_to_review) FROM stdin;
25	suspicious.user	2026-04-10 01:05:55	1	\N	2026-04-10 01:05:55	Suspicious Activity	f	\N	\N	\N	1	0	0	0	\N	69	\N	\N	\N	0	0	f	\N	\N	t	\N	2026-04-10 01:05:55	\N
27	dev.null	2026-04-10 09:09:55	1	\N	2026-04-10 09:09:55	Dev Null	f	\N	\N	\N	1	0	0	0	\N	44	\N	\N	\N	0	0	f	\N	\N	t	\N	2026-04-10 09:09:55	\N
24	jane.smith	2026-04-16 06:50:55	1	\N	2026-04-16 06:50:55	Jane Smith	t	\N	\N	\N	43	9	18	1	\N	93	\N	\N	\N	0	0	f	t	\N	t	\N	2026-04-17 10:32:07.049	\N
23	john.doe	2026-04-13 17:21:55	1	\N	2026-04-13 17:21:55	John Doe	f	\N	\N	\N	39	10	18	1	\N	37	\N	\N	\N	0	0	f	\N	\N	t	\N	2026-04-17 10:32:10.734	\N
26	admin.tester	2026-04-14 12:51:55	1	\N	2026-04-14 12:51:55	Admin Tester	t	\N	\N	\N	39	10	17	1	\N	20	\N	\N	\N	0	0	f	f	\N	t	\N	2026-04-17 10:32:10.734	\N
\.


--
-- Data for Name: event_country; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_country (id, key, country, total_visit, total_ip, total_account, lastseen, created, updated) FROM stdin;
31	1	14	2	1	0	2026-04-11 21:10:55	2026-04-16 17:39:55.345448	2026-04-13 12:41:55
32	1	31	2	1	0	2026-04-10 14:39:55	2026-04-16 17:39:55.350407	2026-04-10 07:35:55
33	1	40	2	1	0	2026-04-10 08:08:55	2026-04-16 17:39:55.354428	2026-04-13 13:24:55
34	1	47	2	1	0	2026-04-13 20:26:55	2026-04-16 17:39:55.358228	2026-04-14 21:04:55
35	1	85	2	1	0	2026-04-12 13:52:55	2026-04-16 17:39:55.361979	2026-04-13 10:19:55
36	1	78	2	1	0	2026-04-14 06:52:55	2026-04-16 17:39:55.365775	2026-04-10 04:39:55
37	1	237	2	1	0	2026-04-11 13:22:55	2026-04-16 17:39:55.369457	2026-04-15 08:46:55
38	1	104	2	1	0	2026-04-15 04:32:55	2026-04-16 17:39:55.372937	2026-04-15 19:33:55
39	1	113	2	1	0	2026-04-13 04:59:55	2026-04-16 17:39:55.37648	2026-04-15 05:23:55
40	1	238	2	1	0	2026-04-15 10:17:55	2026-04-16 17:39:55.380663	2026-04-10 19:49:55
\.


--
-- Data for Name: event_device; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_device (id, account_id, key, created, lastseen, updated, user_agent, lang, total_visit) FROM stdin;
13	23	1	2026-04-13 17:21:55	2026-04-13 17:21:55	2026-04-13 17:21:55	9	\N	0
14	24	1	2026-04-16 06:50:55	2026-04-16 06:50:55	2026-04-16 06:50:55	10	\N	0
15	25	1	2026-04-10 01:05:55	2026-04-10 01:05:55	2026-04-10 01:05:55	11	\N	0
16	26	1	2026-04-14 12:51:55	2026-04-14 12:51:55	2026-04-14 12:51:55	9	\N	0
17	27	1	2026-04-10 09:09:55	2026-04-10 09:09:55	2026-04-10 09:09:55	10	\N	0
\.


--
-- Data for Name: event_domain; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_domain (id, key, domain, ip, geo_ip, geo_html, web_server, hostname, emails, phone, discovery_date, blockdomains, disposable_domains, total_visit, total_account, lastseen, created, updated, free_email_provider, tranco_rank, creation_date, expiration_date, return_code, closest_snapshot, checked, mx_record, disabled) FROM stdin;
\.


--
-- Data for Name: event_email; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_email (id, account_id, email, lastseen, created, key, checked, data_breach, profiles, blockemails, domain_contact_email, domain, fraud_detected, hash, alert_list, data_breaches, earliest_breach) FROM stdin;
\.


--
-- Data for Name: event_error_type; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_error_type (id, value, name) FROM stdin;
2	critical_validation_error	Request failed
3	critical_error	Request failed
0	success	Success
1	validation_error	Success with warnings
4	rate_limit_exceeded	Rate limit exceeded
\.


--
-- Data for Name: event_field_audit; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_field_audit (id, key, field_id, field_name, lastseen, created, updated, total_visit, total_account, total_edit) FROM stdin;
\.


--
-- Data for Name: event_field_audit_trail; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_field_audit_trail (id, account_id, key, created, event_id, field_name, old_value, new_value, parent_id, parent_name, field_id) FROM stdin;
\.


--
-- Data for Name: event_http_method; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_http_method (id, value, name) FROM stdin;
1	get	GET
2	post	POST
3	head	HEAD
4	put	PUT
5	delete	DELETE
6	patch	PATCH
7	trace	TRACE
8	connect	CONNECT
9	options	OPTIONS
10	link	LINK
11	unlink	UNLINK
\.


--
-- Data for Name: event_incorrect; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_incorrect (id, payload, created, errors, traceid, key) FROM stdin;
\.


--
-- Data for Name: event_ip; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_ip (id, ip, key, country, cidr, data_center, tor, vpn, checked, relay, lastseen, created, updated, lastcheck, total_visit, blocklist, isp, shared, domains_count, fraud_detected, hash, alert_list, starlink) FROM stdin;
41	104.26.1.10	1	14	\N	t	t	t	f	\N	2026-04-11 21:10:55	2026-04-16 17:39:55.340441	2026-04-11 21:10:55	\N	0	\N	20	0	\N	t	\N	\N	\N
42	104.26.1.11	1	31	\N	f	f	f	f	\N	2026-04-10 14:39:55	2026-04-16 17:39:55.348093	2026-04-10 14:39:55	\N	0	\N	21	0	\N	f	\N	\N	\N
43	104.26.1.12	1	40	\N	f	f	f	f	\N	2026-04-10 08:08:55	2026-04-16 17:39:55.352194	2026-04-10 08:08:55	\N	0	\N	22	0	\N	f	\N	\N	\N
44	104.26.1.13	1	47	\N	t	f	f	f	\N	2026-04-13 20:26:55	2026-04-16 17:39:55.356095	2026-04-13 20:26:55	\N	0	\N	23	0	\N	f	\N	\N	\N
45	104.26.1.14	1	85	\N	f	f	f	f	\N	2026-04-12 13:52:55	2026-04-16 17:39:55.359851	2026-04-12 13:52:55	\N	0	\N	24	0	\N	f	\N	\N	\N
46	104.26.1.15	1	78	\N	f	f	t	f	\N	2026-04-14 06:52:55	2026-04-16 17:39:55.363617	2026-04-14 06:52:55	\N	0	\N	25	0	\N	t	\N	\N	\N
47	104.26.1.16	1	237	\N	t	f	f	f	\N	2026-04-11 13:22:55	2026-04-16 17:39:55.367352	2026-04-11 13:22:55	\N	0	\N	26	0	\N	f	\N	\N	\N
48	104.26.1.17	1	104	\N	f	t	f	f	\N	2026-04-15 04:32:55	2026-04-16 17:39:55.370997	2026-04-15 04:32:55	\N	0	\N	27	0	\N	f	\N	\N	\N
49	104.26.1.18	1	113	\N	f	f	f	f	\N	2026-04-13 04:59:55	2026-04-16 17:39:55.374453	2026-04-13 04:59:55	\N	0	\N	20	0	\N	f	\N	\N	\N
50	104.26.1.19	1	238	\N	t	f	f	f	\N	2026-04-15 10:17:55	2026-04-16 17:39:55.378128	2026-04-15 10:17:55	\N	0	\N	21	0	\N	f	\N	\N	\N
51	104.26.1.20	1	14	\N	f	f	t	f	\N	2026-04-13 12:41:55	2026-04-16 17:39:55.382201	2026-04-13 12:41:55	\N	0	\N	22	0	\N	t	\N	\N	\N
52	8.8.8.11	1	31	\N	f	f	f	f	\N	2026-04-10 07:35:55	2026-04-16 17:39:55.38727	2026-04-10 07:35:55	\N	0	\N	23	0	\N	f	\N	\N	\N
53	8.8.8.12	1	40	\N	t	f	f	f	\N	2026-04-13 13:24:55	2026-04-16 17:39:55.390987	2026-04-13 13:24:55	\N	0	\N	24	0	\N	f	\N	\N	\N
54	8.8.8.13	1	47	\N	f	f	f	f	\N	2026-04-14 21:04:55	2026-04-16 17:39:55.394444	2026-04-14 21:04:55	\N	0	\N	25	0	\N	f	\N	\N	\N
55	8.8.8.14	1	85	\N	f	t	f	f	\N	2026-04-13 10:19:55	2026-04-16 17:39:55.398204	2026-04-13 10:19:55	\N	0	\N	26	0	\N	f	\N	\N	\N
56	8.8.8.15	1	78	\N	t	f	t	f	\N	2026-04-10 04:39:55	2026-04-16 17:39:55.401839	2026-04-10 04:39:55	\N	0	\N	27	0	\N	t	\N	\N	\N
57	8.8.8.16	1	237	\N	f	f	f	f	\N	2026-04-15 08:46:55	2026-04-16 17:39:55.405234	2026-04-15 08:46:55	\N	0	\N	20	0	\N	f	\N	\N	\N
58	8.8.8.17	1	104	\N	f	f	f	f	\N	2026-04-15 19:33:55	2026-04-16 17:39:55.408996	2026-04-15 19:33:55	\N	0	\N	21	0	\N	f	\N	\N	\N
59	8.8.8.18	1	113	\N	t	f	f	f	\N	2026-04-15 05:23:55	2026-04-16 17:39:55.412352	2026-04-15 05:23:55	\N	0	\N	22	0	\N	f	\N	\N	\N
60	8.8.8.19	1	238	\N	f	f	f	f	\N	2026-04-10 19:49:55	2026-04-16 17:39:55.416013	2026-04-10 19:49:55	\N	0	\N	23	0	\N	f	\N	\N	\N
\.


--
-- Data for Name: event_isp; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_isp (id, key, asn, name, description, total_visit, total_account, lastseen, created, updated, total_ip) FROM stdin;
20	1	8075	Google Cloud	\N	0	0	\N	2026-04-16 17:39:55.325575	2026-04-16 17:39:55.325575	0
21	1	16509	Amazon Data Services	\N	0	0	\N	2026-04-16 17:39:55.32898	2026-04-16 17:39:55.32898	0
22	1	14061	DigitalOcean	\N	0	0	\N	2026-04-16 17:39:55.330606	2026-04-16 17:39:55.330606	0
23	1	7922	Comcast Cable	\N	0	0	\N	2026-04-16 17:39:55.332173	2026-04-16 17:39:55.332173	0
24	1	2856	British Telecommunications	\N	0	0	\N	2026-04-16 17:39:55.33366	2026-04-16 17:39:55.33366	0
25	1	3320	Deutsche Telekom	\N	0	0	\N	2026-04-16 17:39:55.33514	2026-04-16 17:39:55.33514	0
26	1	55836	Reliance JioInfo	\N	0	0	\N	2026-04-16 17:39:55.336881	2026-04-16 17:39:55.336881	0
27	1	4134	China Telecom	\N	0	0	\N	2026-04-16 17:39:55.338403	2026-04-16 17:39:55.338403	0
\.


--
-- Data for Name: event_logbook; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_logbook (id, ended, key, ip, event, error_type, error_text, raw, started, endpoint) FROM stdin;
12	2026-04-15 18:20:10.426	1	192.168.1.1	1	0	\N	{"test":true}	2026-04-15 18:20:10.426	/sensor/
13	2026-04-15 18:20:10.431	1	192.168.1.1	2	0	\N	{"test":true}	2026-04-15 18:20:10.431	/sensor/
14	2026-04-15 18:20:10.435	1	192.168.1.1	3	0	\N	{"test":true}	2026-04-15 18:20:10.435	/sensor/
15	2026-04-15 18:20:10.439	1	192.168.1.1	4	0	\N	{"test":true}	2026-04-15 18:20:10.439	/sensor/
16	2026-04-15 18:20:10.443	1	192.168.1.1	5	0	\N	{"test":true}	2026-04-15 18:20:10.443	/sensor/
17	2026-04-15 18:20:10.447	1	192.168.1.1	6	0	\N	{"test":true}	2026-04-15 18:20:10.447	/sensor/
18	2026-04-15 18:20:10.453	1	192.168.1.1	7	0	\N	{"test":true}	2026-04-15 18:20:10.453	/sensor/
19	2026-04-15 18:20:10.457	1	192.168.1.1	8	0	\N	{"test":true}	2026-04-15 18:20:10.457	/sensor/
20	2026-04-15 18:20:10.46	1	192.168.1.1	9	0	\N	{"test":true}	2026-04-15 18:20:10.46	/sensor/
21	2026-04-15 18:20:10.463	1	192.168.1.1	10	0	\N	{"test":true}	2026-04-15 18:20:10.463	/sensor/
22	2026-04-15 18:20:10.467	1	192.168.1.1	11	0	\N	{"test":true}	2026-04-15 18:20:10.467	/sensor/
23	2026-04-15 18:20:10.471	1	192.168.1.1	12	0	\N	{"test":true}	2026-04-15 18:20:10.471	/sensor/
24	2026-04-15 18:20:10.475	1	192.168.1.1	13	0	\N	{"test":true}	2026-04-15 18:20:10.475	/sensor/
25	2026-04-15 18:20:10.479	1	192.168.1.1	14	0	\N	{"test":true}	2026-04-15 18:20:10.479	/sensor/
26	2026-04-15 18:20:10.483	1	192.168.1.1	15	0	\N	{"test":true}	2026-04-15 18:20:10.483	/sensor/
27	2026-04-15 18:20:10.486	1	192.168.1.1	16	0	\N	{"test":true}	2026-04-15 18:20:10.486	/sensor/
28	2026-04-15 18:20:10.49	1	192.168.1.1	17	0	\N	{"test":true}	2026-04-15 18:20:10.49	/sensor/
29	2026-04-15 18:20:10.494	1	192.168.1.1	18	0	\N	{"test":true}	2026-04-15 18:20:10.494	/sensor/
30	2026-04-15 18:20:10.498	1	192.168.1.1	19	0	\N	{"test":true}	2026-04-15 18:20:10.498	/sensor/
31	2026-04-15 18:20:10.501	1	192.168.1.1	20	0	\N	{"test":true}	2026-04-15 18:20:10.501	/sensor/
32	2026-04-15 18:20:10.505	1	192.168.1.1	21	0	\N	{"test":true}	2026-04-15 18:20:10.505	/sensor/
33	2026-04-15 18:20:10.509	1	192.168.1.1	22	0	\N	{"test":true}	2026-04-15 18:20:10.509	/sensor/
34	2026-04-15 18:20:10.513	1	192.168.1.1	23	0	\N	{"test":true}	2026-04-15 18:20:10.513	/sensor/
35	2026-04-15 18:20:10.516	1	192.168.1.1	24	0	\N	{"test":true}	2026-04-15 18:20:10.516	/sensor/
36	2026-04-15 18:20:10.52	1	192.168.1.1	25	0	\N	{"test":true}	2026-04-15 18:20:10.52	/sensor/
37	2026-04-15 18:20:10.524	1	192.168.1.1	26	0	\N	{"test":true}	2026-04-15 18:20:10.524	/sensor/
38	2026-04-15 18:20:10.527	1	192.168.1.1	27	0	\N	{"test":true}	2026-04-15 18:20:10.527	/sensor/
39	2026-04-15 18:20:10.531	1	192.168.1.1	28	0	\N	{"test":true}	2026-04-15 18:20:10.531	/sensor/
40	2026-04-15 18:20:10.534	1	192.168.1.1	29	0	\N	{"test":true}	2026-04-15 18:20:10.534	/sensor/
41	2026-04-15 18:20:10.537	1	192.168.1.1	30	0	\N	{"test":true}	2026-04-15 18:20:10.537	/sensor/
42	2026-04-15 18:20:10.541	1	10.0.0.1	31	0	\N	{"test":true}	2026-04-15 18:20:10.541	/sensor/
43	2026-04-15 18:20:10.544	1	10.0.0.1	32	0	\N	{"test":true}	2026-04-15 18:20:10.544	/sensor/
44	2026-04-15 18:20:10.547	1	10.0.0.1	33	0	\N	{"test":true}	2026-04-15 18:20:10.547	/sensor/
45	2026-04-15 18:20:10.551	1	10.0.0.1	34	0	\N	{"test":true}	2026-04-15 18:20:10.551	/sensor/
46	2026-04-15 18:20:10.555	1	10.0.0.1	35	0	\N	{"test":true}	2026-04-15 18:20:10.555	/sensor/
47	2026-04-15 18:20:10	1	\N	1	0	\N	\N	2026-04-15 18:20:10	/sensor/
\.


--
-- Data for Name: event_payload; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_payload (id, key, created, payload) FROM stdin;
\.


--
-- Data for Name: event_phone; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_phone (id, account_id, key, phone_number, calling_country_code, national_format, country_code, validation_errors, mobile_country_code, mobile_network_code, carrier_name, type, lastseen, created, updated, checked, shared, fraud_detected, hash, alert_list, profiles, iso_country_code, invalid) FROM stdin;
\.


--
-- Data for Name: event_referer; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_referer (id, key, referer, lastseen, created) FROM stdin;
\.


--
-- Data for Name: event_session; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_session (id, key, account_id, total_visit, total_device, total_ip, total_country, lastseen, created, updated) FROM stdin;
71639462	1	23	0	0	0	0	2026-04-13 17:21:55	2026-04-13 17:21:55	2026-04-13 17:21:55
10974375	1	24	0	0	0	0	2026-04-16 06:50:55	2026-04-16 06:50:55	2026-04-16 06:50:55
67298695	1	25	0	0	0	0	2026-04-10 01:05:55	2026-04-10 01:05:55	2026-04-10 01:05:55
61994075	1	26	0	0	0	0	2026-04-14 12:51:55	2026-04-14 12:51:55	2026-04-14 12:51:55
38251372	1	27	0	0	0	0	2026-04-10 09:09:55	2026-04-10 09:09:55	2026-04-10 09:09:55
\.


--
-- Data for Name: event_session_stat; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_session_stat (id, session_id, key, created, updated, duration, ip_count, device_count, event_count, country_count, new_ip_count, new_device_count, http_codes, http_methods, event_types, completed) FROM stdin;
2	1776273653	1	2026-04-15 17:30:36.061449	2026-04-15 17:30:36.326058	0	1	1	10	1	0	1	\N	\N	{"2": 1, "3": 1, "4": 1, "5": 1, "6": 1, "7": 1, "8": 1, "9": 1, "10": 1, "11": 1}	f
3	1776273753	1	2026-04-15 17:30:36.061449	2026-04-15 17:30:36.326058	0	1	1	10	1	0	1	\N	\N	{"2": 1, "3": 1, "4": 1, "5": 1, "6": 1, "7": 1, "8": 1, "9": 1, "10": 1, "11": 1}	f
4	1776273853	1	2026-04-15 17:30:36.061449	2026-04-15 17:30:36.326058	0	1	1	10	1	0	1	\N	\N	{"2": 1, "3": 1, "4": 1, "5": 1, "6": 1, "7": 1, "8": 1, "9": 1, "10": 1, "11": 1}	f
5	1776273953	1	2026-04-15 17:30:36.061449	2026-04-15 17:30:36.326058	0	1	1	10	1	0	1	\N	\N	{"2": 1, "3": 1, "4": 1, "5": 1, "6": 1, "7": 1, "8": 1, "9": 1, "10": 1, "11": 1}	f
1	1776273553	1	2026-04-15 17:30:36.061449	2026-04-15 17:30:36.326058	0	1	1	10	1	0	0	\N	\N	{"2": 1, "3": 1, "4": 1, "5": 1, "6": 1, "7": 1, "8": 1, "9": 1, "10": 1, "11": 1}	f
31	101	1	2026-04-15 17:57:53.926174	2026-04-15 18:20:20.948103	0	1	1	10	1	1	1	\N	\N	{"1": 10}	f
32	102	1	2026-04-15 17:57:53.926174	2026-04-15 18:20:20.948103	0	1	1	10	1	1	1	\N	\N	{"1": 10}	f
33	103	1	2026-04-15 17:57:53.926174	2026-04-15 18:20:20.948103	0	1	1	10	1	1	1	\N	\N	{"1": 10}	f
34	104	1	2026-04-15 17:57:53.926174	2026-04-15 18:20:20.948103	0	5	1	5	4	5	1	\N	\N	{"3": 5}	f
\.


--
-- Data for Name: event_type; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_type (id, value, name) FROM stdin;
1	page_view	Page View
2	page_edit	Page Edit
3	page_delete	Page Delete
4	page_search	Page Search
5	account_login	Login
6	account_logout	Logout
7	account_login_fail	Login Fail
8	account_registration	Registration
9	account_email_change	Email Change
10	account_password_change	Password Change
11	account_edit	Account Edit
12	page_error	Page Error
13	field_edit	Field Edit
\.


--
-- Data for Name: event_ua_parsed; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_ua_parsed (id, device, browser_name, browser_version, os_name, os_version, ua, uuid, modified, checked, key, created) FROM stdin;
9	\N	Chrome	\N	Windows 10	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0	\N	\N	f	1	2026-04-16 17:39:55.430336
10	\N	Safari	\N	macOS	\N	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15	\N	\N	f	1	2026-04-16 17:39:55.432326
11	\N	Chrome Mobile	\N	Android 13	\N	Mozilla/5.0 (Linux; Android 13; SM-S901B) Chrome/119.0.0.0 Mobile	\N	\N	f	1	2026-04-16 17:39:55.434101
\.


--
-- Data for Name: event_url; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_url (id, key, url, lastseen, created, updated, title, total_visit, total_ip, total_device, total_account, total_country, http_code, total_edit) FROM stdin;
21	1	/login	2026-04-16 17:39:55.419825	2026-04-16 17:39:55.419825	2026-04-16 17:39:55.419825	Login	0	0	0	0	0	\N	0
22	1	/dashboard	2026-04-16 17:39:55.422372	2026-04-16 17:39:55.422372	2026-04-16 17:39:55.422372	Dashboard	0	0	0	0	0	\N	0
23	1	/api/v1/user	2026-04-16 17:39:55.424021	2026-04-16 17:39:55.424021	2026-04-16 17:39:55.424021	User API	0	0	0	0	0	\N	0
24	1	/settings	2026-04-16 17:39:55.425549	2026-04-16 17:39:55.425549	2026-04-16 17:39:55.425549	Settings	0	0	0	0	0	\N	0
25	1	/admin/users	2026-04-16 17:39:55.427098	2026-04-16 17:39:55.427098	2026-04-16 17:39:55.427098	Users Management	0	0	0	0	0	\N	0
26	1	/billing	2026-04-16 17:39:55.428645	2026-04-16 17:39:55.428645	2026-04-16 17:39:55.428645	Billing	0	0	0	0	0	\N	0
\.


--
-- Data for Name: event_url_query; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.event_url_query (id, key, url, query, lastseen, created) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.migrations (id, name, created_at) FROM stdin;
\.


--
-- Data for Name: queue_account_operation; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.queue_account_operation (id, created, updated, event_account, key, action, status) FROM stdin;
\.


--
-- Data for Name: queue_new_events_cursor; Type: TABLE DATA; Schema: public; Owner: endoguard
--

COPY public.queue_new_events_cursor (last_event_id, locked, updated) FROM stdin;
35	f	2026-04-15 18:20:20.577909
\.


--
-- Name: dshb_api_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.dshb_api_id_seq', 1, true);


--
-- Name: dshb_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.dshb_logs_id_seq', 2, true);


--
-- Name: dshb_manual_check_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.dshb_manual_check_history_id_seq', 1, false);


--
-- Name: dshb_message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.dshb_message_id_seq', 1, false);


--
-- Name: dshb_operators_forgot_password_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.dshb_operators_forgot_password_id_seq', 1, false);


--
-- Name: dshb_operators_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.dshb_operators_id_seq', 1, true);


--
-- Name: dshb_operators_rules_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.dshb_operators_rules_id_seq', 108, true);


--
-- Name: dshb_updates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.dshb_updates_id_seq', 8, true);


--
-- Name: event_account_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_account_id_seq', 27, true);


--
-- Name: event_country_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_country_id_seq', 50, true);


--
-- Name: event_device_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_device_id_seq', 17, true);


--
-- Name: event_domain_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_domain_id_seq', 1, false);


--
-- Name: event_email_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_email_id_seq', 1, false);


--
-- Name: event_field_audit_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_field_audit_id_seq', 1, false);


--
-- Name: event_field_audit_trail_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_field_audit_trail_id_seq', 1, false);


--
-- Name: event_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_id_seq', 492, true);


--
-- Name: event_incorrect_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_incorrect_id_seq', 1, false);


--
-- Name: event_ip_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_ip_id_seq', 60, true);


--
-- Name: event_isp_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_isp_id_seq', 27, true);


--
-- Name: event_logbook_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_logbook_id_seq', 47, true);


--
-- Name: event_payload_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_payload_id_seq', 1, false);


--
-- Name: event_phone_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_phone_id_seq', 1, false);


--
-- Name: event_referer_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_referer_id_seq', 1, false);


--
-- Name: event_session_stat_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_session_stat_id_seq', 126, true);


--
-- Name: event_ua_parsed_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_ua_parsed_id_seq', 11, true);


--
-- Name: event_url_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_url_id_seq', 26, true);


--
-- Name: event_url_query_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.event_url_query_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.migrations_id_seq', 1, false);


--
-- Name: queue_account_operation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.queue_account_operation_id_seq', 21, true);


--
-- Name: session_id_seq; Type: SEQUENCE SET; Schema: public; Owner: endoguard
--

SELECT pg_catalog.setval('public.session_id_seq', 1, false);


--
-- Name: countries countries_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.countries
    ADD CONSTRAINT countries_id_pkey PRIMARY KEY (id);


--
-- Name: dshb_api_co_owners dshb_api_co_owners_operator_api_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_api_co_owners
    ADD CONSTRAINT dshb_api_co_owners_operator_api_pkey PRIMARY KEY (operator, api);


--
-- Name: dshb_api_co_owners dshb_api_co_owners_operator_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_api_co_owners
    ADD CONSTRAINT dshb_api_co_owners_operator_key UNIQUE (operator);


--
-- Name: dshb_api dshb_api_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_api
    ADD CONSTRAINT dshb_api_id_pkey PRIMARY KEY (id);


--
-- Name: dshb_api dshb_api_key_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_api
    ADD CONSTRAINT dshb_api_key_key UNIQUE (key);


--
-- Name: dshb_logs dshb_logs_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_logs
    ADD CONSTRAINT dshb_logs_id_pkey PRIMARY KEY (id);


--
-- Name: dshb_manual_check_history dshb_manual_check_history_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_manual_check_history
    ADD CONSTRAINT dshb_manual_check_history_id_pkey PRIMARY KEY (id);


--
-- Name: dshb_operators_forgot_password dshb_operators_forgot_password_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators_forgot_password
    ADD CONSTRAINT dshb_operators_forgot_password_pkey PRIMARY KEY (id);


--
-- Name: dshb_operators_forgot_password dshb_operators_forgot_password_renew_key_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators_forgot_password
    ADD CONSTRAINT dshb_operators_forgot_password_renew_key_key UNIQUE (renew_key);


--
-- Name: dshb_operators dshb_operators_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators
    ADD CONSTRAINT dshb_operators_id_pkey PRIMARY KEY (id);


--
-- Name: dshb_operators_rules dshb_operators_rules_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators_rules
    ADD CONSTRAINT dshb_operators_rules_id_pkey PRIMARY KEY (id);


--
-- Name: dshb_operators_rules dshb_operators_rules_key_rule_uid_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators_rules
    ADD CONSTRAINT dshb_operators_rules_key_rule_uid_key UNIQUE (key, rule_uid);


--
-- Name: dshb_rules dshb_rules_uid_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_rules
    ADD CONSTRAINT dshb_rules_uid_pkey PRIMARY KEY (uid);


--
-- Name: dshb_sessions dshb_sessions_session_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_sessions
    ADD CONSTRAINT dshb_sessions_session_id_pkey PRIMARY KEY (session_id);


--
-- Name: dshb_updates dshb_updates_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_updates
    ADD CONSTRAINT dshb_updates_id_pkey PRIMARY KEY (id);


--
-- Name: dshb_updates dshb_updates_service_version_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_updates
    ADD CONSTRAINT dshb_updates_service_version_key UNIQUE (service, version);


--
-- Name: event_account event_account_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_account
    ADD CONSTRAINT event_account_id_pkey PRIMARY KEY (id);


--
-- Name: event_account event_account_userid_key_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_account
    ADD CONSTRAINT event_account_userid_key_key UNIQUE (userid, key);


--
-- Name: event_country event_country_country_key_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_country
    ADD CONSTRAINT event_country_country_key_key UNIQUE (country, key);


--
-- Name: event_country event_country_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_country
    ADD CONSTRAINT event_country_id_pkey PRIMARY KEY (id);


--
-- Name: event_device event_device_account_id_key_user_agent_lang_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_device
    ADD CONSTRAINT event_device_account_id_key_user_agent_lang_key UNIQUE (account_id, key, user_agent, lang);


--
-- Name: event_device event_device_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_device
    ADD CONSTRAINT event_device_id_pkey PRIMARY KEY (id);


--
-- Name: event_domain event_domain_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_domain
    ADD CONSTRAINT event_domain_id_pkey PRIMARY KEY (id);


--
-- Name: event_domain event_domain_key_domain_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_domain
    ADD CONSTRAINT event_domain_key_domain_key UNIQUE (key, domain);


--
-- Name: event_email event_email_account_id_email_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_email
    ADD CONSTRAINT event_email_account_id_email_key UNIQUE (account_id, email);


--
-- Name: event_email event_email_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_email
    ADD CONSTRAINT event_email_id_pkey PRIMARY KEY (id);


--
-- Name: event_error_type event_error_type_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_error_type
    ADD CONSTRAINT event_error_type_id_pkey PRIMARY KEY (id);


--
-- Name: event_field_audit event_field_audit_field_id_key_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_field_audit
    ADD CONSTRAINT event_field_audit_field_id_key_key UNIQUE (field_id, key);


--
-- Name: event_field_audit event_field_audit_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_field_audit
    ADD CONSTRAINT event_field_audit_id_pkey PRIMARY KEY (id);


--
-- Name: event_field_audit_trail event_field_audit_trail_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_field_audit_trail
    ADD CONSTRAINT event_field_audit_trail_id_pkey PRIMARY KEY (id);


--
-- Name: event_http_method event_http_method_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_http_method
    ADD CONSTRAINT event_http_method_id_pkey PRIMARY KEY (id);


--
-- Name: event_incorrect event_incorrect_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_incorrect
    ADD CONSTRAINT event_incorrect_id_pkey PRIMARY KEY (id);


--
-- Name: event_ip event_ip_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ip
    ADD CONSTRAINT event_ip_id_pkey PRIMARY KEY (id);


--
-- Name: event_ip event_ip_key_ip; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ip
    ADD CONSTRAINT event_ip_key_ip UNIQUE (key, ip);


--
-- Name: event_isp event_isp_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_isp
    ADD CONSTRAINT event_isp_id_pkey PRIMARY KEY (id);


--
-- Name: event_isp event_isp_key_asn_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_isp
    ADD CONSTRAINT event_isp_key_asn_key UNIQUE (key, asn);


--
-- Name: event_payload event_payload_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_payload
    ADD CONSTRAINT event_payload_id_pkey PRIMARY KEY (id);


--
-- Name: event_phone event_phone_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_phone
    ADD CONSTRAINT event_phone_id_pkey PRIMARY KEY (id);


--
-- Name: event_phone event_phone_key_account_id_phone_number_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_phone
    ADD CONSTRAINT event_phone_key_account_id_phone_number_key UNIQUE (key, account_id, phone_number);


--
-- Name: event_referer event_referer_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_referer
    ADD CONSTRAINT event_referer_id_pkey PRIMARY KEY (id);


--
-- Name: event_referer event_referer_referer_key_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_referer
    ADD CONSTRAINT event_referer_referer_key_key UNIQUE (referer, key);


--
-- Name: event_session_stat event_session_stat_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_session_stat
    ADD CONSTRAINT event_session_stat_id_pkey PRIMARY KEY (id);


--
-- Name: event_type event_type_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_type
    ADD CONSTRAINT event_type_id_pkey PRIMARY KEY (id);


--
-- Name: event_ua_parsed event_ua_parsed_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ua_parsed
    ADD CONSTRAINT event_ua_parsed_id_pkey PRIMARY KEY (id);


--
-- Name: event_ua_parsed event_ua_parsed_ua_key_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ua_parsed
    ADD CONSTRAINT event_ua_parsed_ua_key_key UNIQUE (ua, key);


--
-- Name: event_url event_url_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_url
    ADD CONSTRAINT event_url_id_pkey PRIMARY KEY (id);


--
-- Name: event_url_query event_url_query_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_url_query
    ADD CONSTRAINT event_url_query_id_pkey PRIMARY KEY (id);


--
-- Name: event_url_query event_url_query_key_url_query_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_url_query
    ADD CONSTRAINT event_url_query_key_url_query_key UNIQUE (key, url, query);


--
-- Name: event_url event_url_url_key_key; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_url
    ADD CONSTRAINT event_url_url_key_key UNIQUE (url, key);


--
-- Name: migrations migrations_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_id_pkey PRIMARY KEY (id);


--
-- Name: queue_account_operation queue_account_operation_id_pkey; Type: CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.queue_account_operation
    ADD CONSTRAINT queue_account_operation_id_pkey PRIMARY KEY (id);


--
-- Name: countries_iso_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX countries_iso_uidx ON public.countries USING btree (iso);


--
-- Name: countries_value_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX countries_value_uidx ON public.countries USING btree (value);


--
-- Name: dshb_api_key_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX dshb_api_key_uidx ON public.dshb_api USING btree (key);


--
-- Name: dshb_operators_activation_key_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX dshb_operators_activation_key_uidx ON public.dshb_operators USING btree (activation_key);


--
-- Name: dshb_operators_email_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX dshb_operators_email_uidx ON public.dshb_operators USING btree (email);


--
-- Name: dshb_operators_forgot_password_operator_id_status_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX dshb_operators_forgot_password_operator_id_status_idx ON public.dshb_operators_forgot_password USING btree (operator_id, status);


--
-- Name: event_account_added_to_review_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_added_to_review_idx ON public.event_account USING btree (added_to_review);


--
-- Name: event_account_id_key_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX event_account_id_key_uidx ON public.event_account USING btree (id, key);


--
-- Name: event_account_id_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX event_account_id_uidx ON public.event_account USING btree (id);


--
-- Name: event_account_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_idx ON public.event USING btree (account);


--
-- Name: event_account_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_key_idx ON public.event_account USING btree (key);


--
-- Name: event_account_lastseen_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_lastseen_idx ON public.event_account USING btree (lastseen);


--
-- Name: event_account_lastseen_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_lastseen_key_idx ON public.event_account USING btree (lastseen, key);


--
-- Name: event_account_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_lastseen_updated_idx ON public.event_account USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_account_latest_decision_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_latest_decision_key_idx ON public.event_account USING btree (latest_decision, key);


--
-- Name: event_account_score_details_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_score_details_idx ON public.event_account USING gin (score_details);


--
-- Name: event_account_userid_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_account_userid_idx ON public.event_account USING btree (userid);


--
-- Name: event_country_lastseen_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_country_lastseen_key_idx ON public.event_country USING btree (lastseen, key);


--
-- Name: event_country_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_country_lastseen_updated_idx ON public.event_country USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_device_account_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_device_account_id_idx ON public.event_device USING btree (account_id);


--
-- Name: event_device_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_device_idx ON public.event USING btree (device);


--
-- Name: event_device_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_device_key_idx ON public.event_device USING btree (key);


--
-- Name: event_device_lastseen_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_device_lastseen_idx ON public.event_device USING btree (lastseen);


--
-- Name: event_device_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_device_lastseen_updated_idx ON public.event_device USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_device_user_agent_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_device_user_agent_idx ON public.event_device USING btree (user_agent);


--
-- Name: event_domain_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_domain_lastseen_updated_idx ON public.event_domain USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_email_account_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_email_account_id_idx ON public.event_email USING btree (account_id);


--
-- Name: event_email_domain_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_email_domain_idx ON public.event_email USING btree (domain);


--
-- Name: event_email_email_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_email_email_idx ON public.event_email USING btree (email);


--
-- Name: event_email_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_email_idx ON public.event USING btree (email);


--
-- Name: event_email_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_email_key_idx ON public.event_email USING btree (key);


--
-- Name: event_email_lastseen_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_email_lastseen_idx ON public.event_email USING btree (lastseen);


--
-- Name: event_field_audit_field_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_field_audit_field_id_idx ON public.event_field_audit USING btree (field_id);


--
-- Name: event_field_audit_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_field_audit_key_idx ON public.event_field_audit USING btree (key);


--
-- Name: event_field_audit_lastseen_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_field_audit_lastseen_idx ON public.event_field_audit USING btree (lastseen);


--
-- Name: event_field_audit_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_field_audit_lastseen_updated_idx ON public.event_field_audit USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_field_audit_trail_account_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_field_audit_trail_account_id_idx ON public.event_field_audit_trail USING btree (account_id);


--
-- Name: event_field_audit_trail_event_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_field_audit_trail_event_id_idx ON public.event_field_audit_trail USING btree (event_id);


--
-- Name: event_field_audit_trail_field_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_field_audit_trail_field_id_idx ON public.event_field_audit_trail USING btree (field_id);


--
-- Name: event_field_audit_trail_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_field_audit_trail_key_idx ON public.event_field_audit_trail USING btree (key);


--
-- Name: event_id_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX event_id_uidx ON public.event USING btree (id);


--
-- Name: event_ip_account_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_account_key_idx ON public.event USING btree (ip, account, key);


--
-- Name: event_ip_checked_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_checked_idx ON public.event_ip USING btree (checked);


--
-- Name: event_ip_country_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_country_idx ON public.event_ip USING btree (country);


--
-- Name: event_ip_country_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_country_key_idx ON public.event_ip USING btree (country, key);


--
-- Name: event_ip_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_idx ON public.event USING btree (ip);


--
-- Name: event_ip_ip_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_ip_idx ON public.event_ip USING gist (ip inet_ops);


--
-- Name: event_ip_isp_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_isp_idx ON public.event_ip USING btree (isp);


--
-- Name: event_ip_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_key_idx ON public.event_ip USING btree (key);


--
-- Name: event_ip_key_isp_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_key_isp_idx ON public.event_ip USING btree (key, isp);


--
-- Name: event_ip_lastseen_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_lastseen_idx ON public.event_ip USING btree (lastseen);


--
-- Name: event_ip_lastseen_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_lastseen_key_idx ON public.event_ip USING btree (lastseen, key);


--
-- Name: event_ip_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ip_lastseen_updated_idx ON public.event_ip USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_isp_asn_ids; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_isp_asn_ids ON public.event_isp USING btree (asn);


--
-- Name: event_isp_key_id; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_isp_key_id ON public.event_isp USING btree (key);


--
-- Name: event_isp_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_isp_lastseen_updated_idx ON public.event_isp USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_key_idx ON public.event USING btree (key);


--
-- Name: event_key_ip_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_key_ip_idx ON public.event USING btree (ip, key);


--
-- Name: event_logbook_ended_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_logbook_ended_idx ON public.event_logbook USING brin (ended);


--
-- Name: event_logbook_endpoint_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_logbook_endpoint_idx ON public.event_logbook USING btree (endpoint);


--
-- Name: event_logbook_error_type_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_logbook_error_type_idx ON public.event_logbook USING btree (error_type);


--
-- Name: event_logbook_id_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX event_logbook_id_uidx ON public.event_logbook USING btree (id);


--
-- Name: event_logbook_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_logbook_key_idx ON public.event_logbook USING btree (key);


--
-- Name: event_payload_created_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_payload_created_idx ON public.event_payload USING btree (created);


--
-- Name: event_payload_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_payload_idx ON public.event USING btree (payload);


--
-- Name: event_payload_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_payload_key_idx ON public.event_payload USING btree (key);


--
-- Name: event_phone_account_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_phone_account_id_idx ON public.event_phone USING btree (account_id);


--
-- Name: event_phone_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_phone_lastseen_updated_idx ON public.event_phone USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_query_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_query_idx ON public.event USING btree (query);


--
-- Name: event_referer_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_referer_idx ON public.event USING btree (referer);


--
-- Name: event_referer_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_referer_key_idx ON public.event_referer USING btree (key);


--
-- Name: event_session_account_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_session_account_id_idx ON public.event_session USING btree (account_id);


--
-- Name: event_session_id_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_session_id_idx ON public.event USING btree (session_id);


--
-- Name: event_session_id_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX event_session_id_uidx ON public.event_session USING btree (id);


--
-- Name: event_session_lastseen_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_session_lastseen_idx ON public.event_session USING btree (lastseen);


--
-- Name: event_session_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_session_lastseen_updated_idx ON public.event_session USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_session_stat_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_session_stat_key_idx ON public.event_session_stat USING btree (key);


--
-- Name: event_session_stat_session_id_uidx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE UNIQUE INDEX event_session_stat_session_id_uidx ON public.event_session_stat USING btree (session_id);


--
-- Name: event_time_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_time_idx ON public.event USING brin ("time");


--
-- Name: event_time_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_time_key_idx ON public.event USING btree ("time", key);


--
-- Name: event_type_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_type_idx ON public.event USING btree (type);


--
-- Name: event_ua_parsed_device_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_ua_parsed_device_idx ON public.event_ua_parsed USING btree (device);


--
-- Name: event_url_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_url_idx ON public.event USING btree (url);


--
-- Name: event_url_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_url_key_idx ON public.event_url USING btree (key);


--
-- Name: event_url_lastseen_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_url_lastseen_idx ON public.event_url USING btree (lastseen);


--
-- Name: event_url_lastseen_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_url_lastseen_key_idx ON public.event_url USING btree (lastseen, key);


--
-- Name: event_url_lastseen_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_url_lastseen_updated_idx ON public.event_url USING btree (lastseen, updated) WHERE (lastseen >= updated);


--
-- Name: event_url_query_key_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_url_query_key_idx ON public.event_url_query USING btree (key);


--
-- Name: event_url_query_url_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_url_query_url_idx ON public.event_url_query USING btree (url);


--
-- Name: event_url_url_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX event_url_url_idx ON public.event_url USING btree (url);


--
-- Name: queue_account_operation_event_account_key_action_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX queue_account_operation_event_account_key_action_idx ON public.queue_account_operation USING btree (event_account, key, action);


--
-- Name: queue_account_operation_status_updated_idx; Type: INDEX; Schema: public; Owner: endoguard
--

CREATE INDEX queue_account_operation_status_updated_idx ON public.queue_account_operation USING btree (status, updated);


--
-- Name: dshb_api_co_owners dshb_api_co_owners_creator_check; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER dshb_api_co_owners_creator_check BEFORE INSERT OR UPDATE ON public.dshb_api_co_owners FOR EACH ROW EXECUTE FUNCTION public.dshb_api_co_owners_creator_check();


--
-- Name: event_account emp_stamp; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER emp_stamp BEFORE UPDATE ON public.event_account FOR EACH ROW EXECUTE FUNCTION public.event_lastseen();


--
-- Name: event_country emp_stamp; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER emp_stamp BEFORE UPDATE ON public.event_country FOR EACH ROW EXECUTE FUNCTION public.event_lastseen();


--
-- Name: event_device emp_stamp; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER emp_stamp BEFORE UPDATE ON public.event_device FOR EACH ROW EXECUTE FUNCTION public.event_lastseen();


--
-- Name: event_ip emp_stamp; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER emp_stamp BEFORE UPDATE ON public.event_ip FOR EACH ROW EXECUTE FUNCTION public.event_lastseen();


--
-- Name: event_referer emp_stamp; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER emp_stamp BEFORE UPDATE ON public.event_referer FOR EACH ROW EXECUTE FUNCTION public.event_lastseen();


--
-- Name: event_session emp_stamp; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER emp_stamp BEFORE UPDATE ON public.event_session FOR EACH ROW EXECUTE FUNCTION public.event_lastseen();


--
-- Name: event_url emp_stamp; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER emp_stamp BEFORE UPDATE ON public.event_url FOR EACH ROW EXECUTE FUNCTION public.event_lastseen();


--
-- Name: event_url_query emp_stamp; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER emp_stamp BEFORE UPDATE ON public.event_url_query FOR EACH ROW EXECUTE FUNCTION public.event_lastseen();


--
-- Name: queue_new_events_cursor queue_new_events_cursor_trigger; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER queue_new_events_cursor_trigger BEFORE INSERT ON public.queue_new_events_cursor FOR EACH ROW EXECUTE FUNCTION public.queue_new_events_cursor_check();


--
-- Name: dshb_operators_rules restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.dshb_operators_rules FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_account restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_account FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_country restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_country FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_device restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_device FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_domain restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_domain FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_email restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_email FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_field_audit restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_field_audit FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_ip restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_ip FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_isp restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_isp FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_logbook restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_logbook FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_phone restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_phone FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_referer restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_referer FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_session restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_session FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_ua_parsed restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_ua_parsed FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_url restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_url FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: event_url_query restrict_update; Type: TRIGGER; Schema: public; Owner: endoguard
--

CREATE TRIGGER restrict_update BEFORE UPDATE ON public.event_url_query FOR EACH ROW EXECUTE FUNCTION public.restrict_update();


--
-- Name: dshb_api_co_owners dshb_api_co_owners_api_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_api_co_owners
    ADD CONSTRAINT dshb_api_co_owners_api_fkey FOREIGN KEY (api) REFERENCES public.dshb_api(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: dshb_api_co_owners dshb_api_co_owners_operator_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_api_co_owners
    ADD CONSTRAINT dshb_api_co_owners_operator_fkey FOREIGN KEY (operator) REFERENCES public.dshb_operators(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: dshb_api dshb_api_creator_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_api
    ADD CONSTRAINT dshb_api_creator_fkey FOREIGN KEY (creator) REFERENCES public.dshb_operators(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: dshb_manual_check_history dshb_manual_check_history_operator_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_manual_check_history
    ADD CONSTRAINT dshb_manual_check_history_operator_fkey FOREIGN KEY (operator) REFERENCES public.dshb_operators(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: dshb_operators_forgot_password dshb_operators_forgot_password_operator_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators_forgot_password
    ADD CONSTRAINT dshb_operators_forgot_password_operator_id_fkey FOREIGN KEY (operator_id) REFERENCES public.dshb_operators(id) ON DELETE CASCADE;


--
-- Name: dshb_operators_rules dshb_operators_rules_rule_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.dshb_operators_rules
    ADD CONSTRAINT dshb_operators_rules_rule_uid_fkey FOREIGN KEY (rule_uid) REFERENCES public.dshb_rules(uid) ON DELETE CASCADE;


--
-- Name: event event_account_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_account_fkey FOREIGN KEY (account) REFERENCES public.event_account(id);


--
-- Name: event_account event_account_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_account
    ADD CONSTRAINT event_account_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event_country event_country_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_country
    ADD CONSTRAINT event_country_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event_device event_device_account_id_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_device
    ADD CONSTRAINT event_device_account_id_key_fkey FOREIGN KEY (account_id, key) REFERENCES public.event_account(id, key) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: event event_device_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_device_fkey FOREIGN KEY (device) REFERENCES public.event_device(id);


--
-- Name: event_device event_device_user_agent_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_device
    ADD CONSTRAINT event_device_user_agent_fkey FOREIGN KEY (user_agent) REFERENCES public.event_ua_parsed(id);


--
-- Name: event_domain event_domain_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_domain
    ADD CONSTRAINT event_domain_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event_email event_email_account_id_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_email
    ADD CONSTRAINT event_email_account_id_key_fkey FOREIGN KEY (account_id, key) REFERENCES public.event_account(id, key) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: event_email event_email_domain_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_email
    ADD CONSTRAINT event_email_domain_fkey FOREIGN KEY (domain) REFERENCES public.event_domain(id);


--
-- Name: event event_email_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_email_fkey FOREIGN KEY (email) REFERENCES public.event_email(id);


--
-- Name: event_field_audit event_field_audit_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_field_audit
    ADD CONSTRAINT event_field_audit_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event_field_audit_trail event_field_audit_trail_account_id_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_field_audit_trail
    ADD CONSTRAINT event_field_audit_trail_account_id_key_fkey FOREIGN KEY (account_id, key) REFERENCES public.event_account(id, key) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: event_field_audit_trail event_field_audit_trail_field_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_field_audit_trail
    ADD CONSTRAINT event_field_audit_trail_field_id_fkey FOREIGN KEY (field_id) REFERENCES public.event_field_audit(id);


--
-- Name: event event_http_method_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_http_method_fkey FOREIGN KEY (http_method) REFERENCES public.event_http_method(id);


--
-- Name: event event_ip_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_ip_fkey FOREIGN KEY (ip) REFERENCES public.event_ip(id);


--
-- Name: event_ip event_ip_isp_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ip
    ADD CONSTRAINT event_ip_isp_fkey FOREIGN KEY (isp) REFERENCES public.event_isp(id);


--
-- Name: event_ip event_ip_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ip
    ADD CONSTRAINT event_ip_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event_isp event_isp_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_isp
    ADD CONSTRAINT event_isp_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event event_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event_logbook event_logbook_error_type_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_logbook
    ADD CONSTRAINT event_logbook_error_type_fkey FOREIGN KEY (error_type) REFERENCES public.event_error_type(id);


--
-- Name: event_logbook event_logbook_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_logbook
    ADD CONSTRAINT event_logbook_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event event_payload_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_payload_fkey FOREIGN KEY (payload) REFERENCES public.event_payload(id);


--
-- Name: event_phone event_phone_account_id_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_phone
    ADD CONSTRAINT event_phone_account_id_key_fkey FOREIGN KEY (account_id, key) REFERENCES public.event_account(id, key) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: event event_phone_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_phone_fkey FOREIGN KEY (phone) REFERENCES public.event_phone(id);


--
-- Name: event event_query_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_query_fkey FOREIGN KEY (query) REFERENCES public.event_url_query(id);


--
-- Name: event event_referer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_referer_fkey FOREIGN KEY (referer) REFERENCES public.event_referer(id);


--
-- Name: event_referer event_referer_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_referer
    ADD CONSTRAINT event_referer_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event event_session_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_session_id_fkey FOREIGN KEY (session_id) REFERENCES public.event_session(id);


--
-- Name: event_session event_session_id_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_session
    ADD CONSTRAINT event_session_id_key_fkey FOREIGN KEY (account_id, key) REFERENCES public.event_account(id, key) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: event_session event_session_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_session
    ADD CONSTRAINT event_session_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event event_type_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_type_fkey FOREIGN KEY (type) REFERENCES public.event_type(id);


--
-- Name: event_ua_parsed event_ua_parsed_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_ua_parsed
    ADD CONSTRAINT event_ua_parsed_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event event_url_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_url_fkey FOREIGN KEY (url) REFERENCES public.event_url(id);


--
-- Name: event_url event_url_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_url
    ADD CONSTRAINT event_url_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event_url_query event_url_query_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_url_query
    ADD CONSTRAINT event_url_query_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON DELETE CASCADE;


--
-- Name: event_url_query event_url_query_url_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.event_url_query
    ADD CONSTRAINT event_url_query_url_fkey FOREIGN KEY (url) REFERENCES public.event_url(id);


--
-- Name: queue_account_operation queue_account_operation_event_account_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.queue_account_operation
    ADD CONSTRAINT queue_account_operation_event_account_fkey FOREIGN KEY (event_account) REFERENCES public.event_account(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: queue_account_operation queue_account_operation_key_fkey; Type: FK CONSTRAINT; Schema: public; Owner: endoguard
--

ALTER TABLE ONLY public.queue_account_operation
    ADD CONSTRAINT queue_account_operation_key_fkey FOREIGN KEY (key) REFERENCES public.dshb_api(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict PNIxVmQt3XjQfLdpwqBqtJrnJebjdhg37Yz3jQGNe5XEwkTTjMjeqVyThOeM0rL

