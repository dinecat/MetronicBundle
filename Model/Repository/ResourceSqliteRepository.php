<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\Model\Repository;

/**
 * Resource repository manager for SQLite.
 * @package DinecatThemeBundle\Model\Repository
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class ResourceSqliteRepository implements ResourceRepositoryInterface
{
    /**
     * @var \PDO
     */
    protected $storage;

    /**
     * @var array {
     *     @var string  $data_path  Path to database.
     * }
     */
    protected $options;

    /**
     * Constructor.
     * @param   array   $options {
     *     @var string  $data_path  Path to database.
     * }
     */
    public function __construct(array $options)
    {
        $this->storage = new \PDO('sqlite:' . $options['data_path']);
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getResource($theme, $name)
    {
        $stmt = $this->storage->prepare('SELECT definition FROM resource WHERE theme = :theme AND name = :name');
        $stmt->execute(['theme' => $theme, 'name' => $name]);

        if ($resource = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return json_decode($resource['definition'], true);
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function setResource($theme, $name, array $definition)
    {
        $this->initDatabase();
        $data = json_encode($definition, JSON_UNESCAPED_UNICODE);

        $stmt = $this->storage->prepare('SELECT id, definition FROM resource WHERE theme = :theme AND name = :name');
        $stmt->execute(['theme' => $theme, 'name' => $name]);

        if ($resource = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($data === $resource['definition']) {
                return true;
            } else {
                $stmt = $this->storage->prepare('UPDATE resource SET definition = :definition WHERE id = :id');
                $stmt->execute(['id' => $resource['id'], 'definition' => $data]);
                return true;
            }
        }

        $stmt = $this->storage->prepare('
            INSERT INTO resource (theme, name, definition, created_at) VALUES (:theme, :name, :definition, :created_at)
        ');
        $stmt->execute(['theme' => $theme, 'name' => $name, ':definition' => $data, 'created_at' => time()]);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function removeResource($theme, $name)
    {
        $stmt = $this->storage->prepare('DELETE FROM resource WHERE theme = :theme AND name = :name');
        $stmt->execute(['theme' => $theme, 'name' => $name]);
        return $this;
    }

    /**
     * Initialization for structures in database.
     */
    protected function initDatabase()
    {
        $this->storage->exec('CREATE TABLE IF NOT EXISTS resource (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme TEXT,
            name TEXT,
            definition BLOB,
            created_at INTEGER
        )');
        $this->storage->exec('CREATE INDEX IF NOT EXISTS ident ON resource (name, theme)');
    }
}
