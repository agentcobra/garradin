<?php

namespace Garradin\Entities\Web;

use Garradin\DB;
use Garradin\Entity;
use Garradin\UserException;
use Garradin\Utils;
use Garradin\Entities\Files\File;

use KD2\DB\EntityManager as EM;

use const Garradin\WWW_URL;

class Page extends Entity
{
	const TABLE = 'web_pages';

	protected $id;
	protected $parent_id;
	protected $type;
	protected $status;
	protected $title;
	protected $uri;

	protected $_types = [
		'id'        => 'int',
		'parent_id' => '?int',
		'type'      => 'int',
		'status'    => 'int',
		'uri'       => 'string',
		'title'     => 'string',
	];

	protected $_file;

	const STATUS_ONLINE = 1;
	const STATUS_DRAFT = 0;

	const TYPE_CATEGORY = 1;
	const TYPE_PAGE = 2;

	protected $_attachments;

	static public function create(int $type, ?int $parent_id, string $title, int $status = self::STATUS_ONLINE): self
	{
		$page = new self;
		$data = compact('type', 'parent_id', 'title', 'status');
		$data['uri'] = $title;
		$data['content'] = '';

		$page->importForm($data);

		return $page;
	}

	public function url(): string
	{
		$url = WWW_URL . $this->uri;

		if ($this->type == self::TYPE_CATEGORY) {
			$url .= '/';
		}

		return $url;
	}

	public function raw(): string
	{
		return $this->file()->fetch();
	}

	public function created(): \DateTime
	{
		return $this->file()->created;
	}

	public function modified(): \DateTime
	{
		return $this->file()->modified;
	}

	public function render(array $options = []): string
	{
		return $this->file()->render($options);
	}

	public function file(): File
	{
		if (null === $this->_file) {
			if ($this->exists()) {
				$this->_file = EM::findOneById(File::class, $this->id());
			}
			else {
				$file = $this->_file = new File;
				$file->type = File::FILE_TYPE_SKRIV;
				$file->context = File::CONTEXT_WEB;
				$file->image = 0;
			}
		}

		return $this->_file;
	}

	public function setFile(File $file)
	{
		$this->_file = $file;
	}

	public function save(): bool
	{
		$file = $this->file();

		$file->set('modified', new \DateTime);
		$file->save();

		$this->id($file->id());

		return parent::save();
	}

	public function selfCheck(): void
	{
		$this->assert($this->type === self::TYPE_CATEGORY || $this->type === self::TYPE_PAGE, 'Unknown page type');
		$this->assert($this->status === self::STATUS_DRAFT || $this->status === self::STATUS_ONLINE, 'Unknown page status');
		$this->assert(trim($this->title) !== '', 'Le titre ne peut rester vide');
		$this->assert(trim($this->uri) !== '', 'L\'URI ne peut rester vide');
		$this->assert((bool) $this->_file, 'Fichier manquant');

		$db = DB::getInstance();
		$where = $this->exists() ? sprintf(' AND id != %d', $this->id()) : '';
		$this->assert(!$db->test(self::TABLE, 'uri = ?' . $where, $this->uri), 'Cette adresse URI est déjà utilisée par une autre page, merci d\'en choisir une autre');
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['parent_id']) && is_array($source['parent_id'])) {
			$source['parent_id'] = key($source['parent_id']);
		}

		$file = $this->file();

		if (isset($source['date']) && isset($source['date_time'])) {
			$file->importForm(['created' => sprintf('%s %s', $source['date'], $source['date_time'])]);
		}

		if (!empty($source['encrypted']) ) {
			$file->set('type', File::FILE_TYPE_ENCRYPTED);
		}
		else {
			$file->set('type', File::FILE_TYPE_SKRIV);
		}

		if (!empty($source['uri'])) {
			$source['uri'] = Utils::transformTitleToURI($source['uri']);
			$file->set('name', $source['uri'] . '.skriv');
		}

		if (isset($source['content']) && sha1($source['content']) != $file->hash) {
			$file->store(null, $source['content']);
		}

		return $this->import($source);
	}

	public function getBreadcrumbs()
	{
		$sql = '
			WITH RECURSIVE parents(id, name, parent_id, level) AS (
				SELECT id, title, parent_id, 1 FROM web_pages WHERE id = ?
				UNION ALL
				SELECT p.id, p.title, p.parent_id, level + 1
				FROM web_pages p
					JOIN parents ON p.id = parents.parent_id
			)
			SELECT id, name FROM parents ORDER BY level DESC;';
		return DB::getInstance()->getAssoc($sql, $this->id());
	}

	public function listAttachments(): array
	{
		if (null === $this->_attachments) {
			$this->_attachments = $this->file()->listLinked();
		}

		return $this->_attachments;
	}

	static public function findTaggedAttachments(string $text): array
	{
		preg_match_all('/<<?(?:fichier|image)\s*(?:\|\s*)?(\d+)/', $text, $match, PREG_PATTERN_ORDER);
		preg_match_all('/(?:fichier|image):\/\/(\d+)/', $text, $match2, PREG_PATTERN_ORDER);

		return array_merge($match[1], $match2[1]);
	}

	/**
	 * Return list of images
	 * If $all is FALSE then this will only return images that are not present in the content
	 */
	public function getImageGallery(bool $all = true): array
	{
		return $this->getAttachmentsGallery($all, true);
	}

	/**
	 * Return list of files
	 * If $all is FALSE then this will only return files that are not present in the content
	 */
	public function getAttachmentsGallery(bool $all = true, bool $images = false): array
	{
		$out = [];

		if (!$all) {
			$tagged = $this->findTaggedAttachments($this->raw());
		}

		foreach ($this->listAttachments() as $a) {
			if ($images && !$a->image) {
				continue;
			}
			elseif (!$images && $a->image) {
				continue;
			}

			// Skip
			if (!$all && in_array($a->id, $tagged)) {
				continue;
			}

			$out[] = $a;
		}

		return $out;
	}
}