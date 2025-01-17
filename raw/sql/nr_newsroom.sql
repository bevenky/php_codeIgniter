DROP VIEW IF EXISTS nr_newsroom;

CREATE VIEW nr_newsroom AS 

SELECT
	c.id AS company_id,
	c.company_contact_id AS company_contact_id,
	c.name AS company_name,
	c.newsroom AS name,
	c.source AS source,
	c.date_created as date_created,
	c.newsroom_domain AS domain,
	c.newsroom_timezone AS timezone,
	c.user_id AS user_id,
	c.newsroom_is_active AS is_active,
	c.order_default AS order_default,
	c.is_archived AS is_archived,
	c.is_reseller_controlled AS is_reseller_controlled,
	c.newsroom_domain_base AS domain_base,
	c.is_deleted AS is_deleted
FROM nr_company c