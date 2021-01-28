-- Remove triggers in case they interact with the migration
DROP TRIGGER IF EXISTS wiki_recherche_delete;
DROP TRIGGER IF EXISTS wiki_recherche_update;
DROP TRIGGER IF EXISTS wiki_recherche_contenu_insert;
DROP TRIGGER IF EXISTS wiki_recherche_contenu_chiffre;

ALTER TABLE membres_categories RENAME TO membres_categories_old;
ALTER TABLE config RENAME TO config_old;

.read 1.1.0_schema.sql

INSERT INTO config SELECT * FROM config_old;
DROP TABLE config_old;

DELETE FROM config WHERE key = 'version';

-- Copy droit_wiki value to droit_web and droit_documents
INSERT INTO users_categories
	SELECT id, nom,
		droit_wiki, -- perm_web
		droit_wiki, -- perm_documents
		droit_membres,
		droit_compta,
		droit_inscription,
		droit_connexion,
		droit_config,
		cacher
	FROM membres_categories_old;

DROP TABLE membres_categories_old;

UPDATE recherches SET contenu = REPLACE(contenu, 'id_categorie', 'category_id') WHERE cible = 'membres' AND contenu LIKE '%id_categorie%';

-- Copy existing file contents
INSERT INTO files_contents (id, hash, content, size)
	SELECT id, hash, contenu, taille FROM fichiers_contenu;

-- Copy existing file metadata, including context
INSERT INTO files (id, hash, name, type, created, author_id, image, context, context_ref)
	SELECT f.id, c.hash, nom, type, datetime, NULL, image,
		CASE WHEN t.id THEN 'transaction' ELSE 'file' END, -- context
		CASE WHEN t.id THEN t.fichier ELSE 10000 + w.id END
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		LEFT JOIN fichiers_acc_transactions t ON t.fichier = f.id
		LEFT JOIN fichiers_wiki_pages w ON w.fichier = f.id;

-- Copy wiki pages content
CREATE TEMP TABLE wiki_as_files (old_id, new_id, hash, size, content, name, title, uri, path, old_parent, new_parent, created, modified, author_id, encrypted, image, content_id, type, public);

INSERT INTO wiki_as_files
	SELECT
		id, NULL, sha1(contenu), LENGTH(contenu), contenu,
		uri || '.skriv', titre, uri, NULL, parent, parent,
		date_creation, date_modification, id_auteur, chiffrement, 0, NULL,
		CASE WHEN (SELECT 1 FROM wiki_pages pp WHERE pp.parent = p.id LIMIT 1) THEN 1 ELSE 2 END, -- Type, 1 = category, 2 = page
		CASE WHEN droit_lecture = -1 THEN 1 ELSE 0 END -- public
	FROM wiki_pages p
	INNER JOIN wiki_revisions r ON r.id_page = p.id AND r.revision = p.revision;

-- Build back path, up to ten levels
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;

UPDATE wiki_as_files SET new_parent = NULL;

INSERT INTO files_contents (hash, content, size) SELECT hash, content, size FROM wiki_as_files;
UPDATE wiki_as_files SET content_id = (SELECT fc.id FROM files_contents fc WHERE fc.hash = wiki_as_files.hash);

INSERT INTO files (id, hash, name, type, created, modified, author_id, image, context, context_ref)
	SELECT
		old_id + 10000,
		hash,
		name,
		CASE WHEN encrypted THEN 'text/vnd.skriv.encrypted' ELSE 'text/vnd.skriv' END,
		created,
		modified,
		author_id,
		image,
		CASE WHEN public = 0 THEN 'documents' ELSE 'web' END, -- private wiki page = documents, public wiki page = public website
		CASE WHEN public = 0 THEN path ELSE NULL END -- private wiki page has a path
	FROM wiki_as_files;

INSERT INTO files_search (id, content) SELECT new_id, content FROM wiki_as_files WHERE encrypted = 0;

UPDATE wiki_as_files SET new_id = (SELECT id FROM files WHERE hash = wiki_as_files.hash);
UPDATE wiki_as_files SET new_parent = (SELECT w.new_id FROM wiki_as_files w WHERE w.old_id = wiki_as_files.old_parent);

INSERT INTO web_pages
	SELECT new_id, new_parent, type, 1, uri, title FROM wiki_as_files WHERE public = 1;

-- Link background image file to config
UPDATE files SET context = 'config', context_ref = 'image_fond' WHERE id = (SELECT value FROM config WHERE key = 'image_fond' AND value > 0);

-- Copy connection page as a single file
INSERT INTO files (hash, context, context_ref, name, type, created, author_id)
	SELECT hash, 'config', 'admin_homepage', 'Accueil_connexion.skriv', type, created, author_id
	FROM files WHERE id = (SELECT new_id FROM wiki_as_files WHERE uri = (SELECT value FROM config WHERE key = 'accueil_connexion'));

UPDATE config SET value = (SELECT id FROM files WHERE name = 'Accueil_connexion.skriv') WHERE key = 'accueil_connexion';
UPDATE config SET key = 'admin_homepage' WHERE key = 'accueil_connexion';

-- This is not used anymore
DELETE FROM config WHERE key = 'accueil_wiki';

-- New config key
INSERT INTO config (key, value) VALUES ('telephone_asso', NULL);

DROP TABLE wiki_recherche;

DROP TABLE wiki_pages;
DROP TABLE wiki_revisions;

DROP TABLE fichiers_wiki_pages;
DROP TABLE fichiers_acc_transactions;
DROP TABLE fichiers_membres;

DROP TABLE fichiers;
DROP TABLE fichiers_contenu;