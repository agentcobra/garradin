<?php

namespace Garradin;

use KD2\SMTP;

class Config
{
    protected $fields_types = null;
    protected $config = null;
    protected $modified = [];

    static protected $_instance = null;

    /**
     * Singleton simple
     * @return Config
     */
    static public function getInstance()
    {
        return self::$_instance ?: self::$_instance = new Config;
    }

    static public function deleteInstance()
    {
        self::$_instance = null;
    }

    /**
     * Empêche de cloner l'objet
     * @return void
     */
    private function __clone()
    {
    }

    protected function __construct()
    {
        // Définition des types de données stockées
        $string = '';
        $int = 0;
        $float = 0.0;
        $array = [];
        $bool = false;
        $object = new \stdClass;

        $this->fields_types = [
            'nom_asso'                =>  $string,
            'adresse_asso'            =>  $string,
            'email_asso'              =>  $string,
            'site_asso'               =>  $string,

            'monnaie'                 =>  $string,
            'pays'                    =>  $string,

            'champs_membres'          =>  $object,

            'categorie_membres'       =>  $int,

            'accueil_wiki'            =>  $string,
            'accueil_connexion'       =>  $string,

            'frequence_sauvegardes'   =>  $int,
            'nombre_sauvegardes'      =>  $int,

            'champ_identifiant'       =>  $string,
            'champ_identite'          =>  $string,

            'version'                 =>  $string,
            'last_chart_change'       =>  $int,
            'last_version_check'      =>  $string,

            'couleur1'                =>  $string,
            'couleur2'                =>  $string,
            'image_fond'              =>  $string,

            'desactiver_site'         =>  $bool,
        ];

        $db = DB::getInstance();

        $this->config = $db->getAssoc('SELECT cle, valeur FROM config ORDER BY cle;');

        foreach ($this->config as $key=>&$value)
        {
            if (!array_key_exists($key, $this->fields_types))
            {
                // Ancienne clé de config qui n'est plus utilisée
                continue;
            }

            if (is_array($this->fields_types[$key]))
            {
                $value = explode(',', $value);
            }
            elseif ($key == 'champs_membres')
            {
                $value = new Membres\Champs((string)$value);
            }
            else
            {
                settype($value, gettype($this->fields_types[$key]));
            }
        }
    }

    public function __destruct()
    {
        if (!empty($this->modified))
        {
            $this->save();
        }
    }

    public function save()
    {
        if (empty($this->modified))
            return true;

        $values = [];
        $db = DB::getInstance();

        // Image files
        if (isset($this->modified['image_fond'])) {
            $key = 'image_fond';
            $value =& $this->config[$key];

            if ($current = $db->firstColumn('SELECT valeur FROM config WHERE cle = ?;', $key))
            {
                try {
                    $f = new Fichiers($current);
                    $f->remove();
                }
                catch (\InvalidArgumentException $e) {
                    // Ignore: the file has already been deleted
                }
            }

            if (strlen($value) > 0)
            {
                $content = $value;
                $value = null;
                $f = Fichiers::storeFromBase64($key . '.png', $content);
                $value = $f->id;
                unset($f);
            }
        }

        unset($value, $key);

        $db->begin();

        foreach ($this->modified as $key=>$modified)
        {
            $value = $this->config[$key];

            if (is_array($value))
            {
                $value = implode(',', $value);
            }
            elseif (is_object($value))
            {
                $value = (string) $value;
            }

            $db->preparedQuery('INSERT OR REPLACE INTO config (cle, valeur) VALUES (?, ?);',
                [$key, $value]);
        }

        if (!empty($this->modified['champ_identifiant']))
        {
            // Mettre les champs identifiant vides à NULL pour pouvoir créer un index unique
            $db->exec('UPDATE membres SET '.$this->get('champ_identifiant').' = NULL
                WHERE '.$this->get('champ_identifiant').' = "";');

            // Création de l'index unique
            $db->exec('DROP INDEX IF EXISTS membres_identifiant;');
            $db->exec('CREATE UNIQUE INDEX membres_identifiant ON membres ('.$this->get('champ_identifiant').');');
        }

        $db->commit();

        $this->modified = [];

        return true;
    }

    public function get($key)
    {
        if (!array_key_exists($key, $this->fields_types))
        {
            throw new \OutOfBoundsException('Ce champ est inconnu.');
        }

        if (!array_key_exists($key, $this->config))
        {
            return null;
        }

        return $this->config[$key];
    }

    public function getVersion()
    {
        if (!array_key_exists('version', $this->config))
        {
            return '0';
        }

        return $this->config['version'];
    }

    public function setVersion($version)
    {
        $this->config['version'] = $version;

        $db = DB::getInstance();
        $db->preparedQuery('INSERT OR REPLACE INTO config (cle, valeur) VALUES (?, ?);',
                ['version', $version]);

        return true;
    }

    public function set($key, $value)
    {
        if (!array_key_exists($key, $this->fields_types))
        {
            throw new \OutOfBoundsException('Ce champ est inconnu.');
        }

        if (is_array($this->fields_types[$key]))
        {
            $value = !empty($value) ? (array) $value : [];
        }
        elseif (is_int($this->fields_types[$key]))
        {
            $value = (int) $value;
        }
        elseif (is_float($this->fields_types[$key]))
        {
            $value = (float) $value;
        }
        elseif (is_bool($this->fields_types[$key]))
        {
            $value = (bool) $value;
        }
        elseif (is_string($this->fields_types[$key]))
        {
            $value = (string) $value;
        }

        switch ($key)
        {
            case 'nom_asso':
            {
                if (!trim($value))
                {
                    throw new UserException('Le nom de l\'association ne peut rester vide.');
                }
                break;
            }
            case 'accueil_wiki':
            case 'accueil_connexion':
            {
                $value = trim($value);
                $name = str_replace('accueil_', '', $key);

                if ($value === '')
                {
                    throw new UserException(sprintf('Le nom de la page d\'accueil %s ne peut rester vide.', $name));
                }

                $db = DB::getInstance();

                if (!$db->test('wiki_pages', $db->where('uri', $value))) {
                    throw new UserException(sprintf('Le nom de la page d\'accueil %s ne correspond à aucune page existante, merci de la créer auparavant.', $name));
                }
                break;
            }
            case 'email_asso':
            {
                if (!SMTP::checkEmailIsValid($value, false))
                {
                    throw new UserException('Adresse e-mail invalide.');
                }
                break;
            }
            case 'champs_membres':
            {
                if (!($value instanceOf Membres\Champs))
                {
                    throw new \UnexpectedValueException('$value doit être de type Membres\Champs');
                }
                break;
            }
            case 'champ_identite':
            case 'champ_identifiant':
            {
                $champs = $this->get('champs_membres');
                $db = DB::getInstance();

                // Vérification que le champ existe bien
                if (!$champs->get($value))
                {
                    throw new UserException('Le champ '.$value.' n\'existe pas pour la configuration de '.$key);
                }

                // Vérification que le champ est unique pour l'identifiant
                if ($key == 'champ_identifiant'
                    && !$db->firstColumn('SELECT (COUNT(DISTINCT lower('.$value.')) = COUNT(*))
                        FROM membres WHERE '.$value.' IS NOT NULL AND '.$value.' != \'\';'))
                {
                    throw new UserException('Le champ '.$value.' comporte des doublons et ne peut donc pas servir comme identifiant pour la connexion.');
                }
                break;
            }
            case 'categorie_membres':
            {
                $db = DB::getInstance();
                if (!$db->firstColumn('SELECT 1 FROM membres_categories WHERE id = ?;', $value))
                {
                    throw new UserException('La catégorie de membres par défaut numéro \''.$value.'\' ne semble pas exister.');
                }
                break;
            }
            case 'monnaie':
            {
                if (!trim($value))
                {
                    throw new UserException('La monnaie doit être renseignée.');
                }

                break;
            }
            case 'pays':
            {
                if (!trim($value) || !Utils::getCountryName($value))
                {
                    throw new UserException('Le pays renseigné est invalide.');
                }

                break;
            }
            default:
                break;
        }

        if (!isset($this->config[$key]) || $value !== $this->config[$key])
        {
            $this->config[$key] = $value;
            $this->modified[$key] = true;
        }

        return true;
    }

    public function getFieldsTypes()
    {
        return $this->fields_types;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
