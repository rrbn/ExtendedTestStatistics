<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE


/**
 * Basic plugin file
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 */
class ilExtendedTestStatisticsPlugin extends ilUserInterfaceHookPlugin
{

	protected ?ilExtendedTestStatisticsConfig $config;
    protected ilObjUser $user;

    /**
     * Constructor
     */
    public function __construct(
        \ilDBInterface $db,
        \ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $DIC;
        $this->user = $DIC->user();

        parent::__construct($db, $component_repository, $id);
    }

	/**
	 * After update processing
	 */
    protected function afterUpdate(): void
	{
		parent::afterUpdate();
		ilExtendedTestStatisticsCache::flushAll();
	}

    public function getPluginName(): string
	{
		return "ExtendedTestStatistics";
	}

    public function refreshLanguages()
    {
        $this->getLanguageHandler()->updateLanguages();
    }

	public function getConfig(): ilExtendedTestStatisticsConfig
	{
		if (!isset($this->config)) {
			$this->config = new ilExtendedTestStatisticsConfig($this);
		}
		return $this->config;
	}

    protected function buildLanguageHandler(): ilPluginLanguage
    {
        return new ilExtendedTestStatisticLanguage($this->getPluginInfo());
    }

    /**
	 * Get debugging output of different value formats
	 */
	public function debugFormats(): bool
	{
		return false;
	}

	/**
	 * Get a user preference
	 */
	public function getUserPreference(string $name, string $default = ''): string
	{
        return $this->user->getPref($this->getId() . '_' . $name) ?? $default;
	}


	/**
	 * Set a user preference
	 */
	public function setUserPreference(string $name, string $value) : void
	{
		$this->user->writePref($this->getId() . '_' . $name, $value);
	}
}