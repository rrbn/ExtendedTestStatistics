<?php

// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Data cache for extended test statistics
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 */
class ilExtendedTestStatisticsCache
{

	/** @var integer $test_id		test id (not the object_id) */
	protected $test_id;

	/** @var string $pass_selection		setting for the selected pass */
	protected $pass_selection;

	/** @var array $content		consumer_class => content_key => content */
	protected $content = array();

	/** @var bool $validated  cache is already validated */
	private $validated = false;

	/**
	 * Constructor.
	 * @param integer	$a_test_id	(not the object id)
	 * @param string	$a_pass_selection
	 */
	public function __construct($a_test_id, $a_pass_selection)
	{
		$this->test_id = $a_test_id;
		$this->pass_selection = $a_pass_selection;
	}

	/**
	 * Set the pass selection
	 * @param string	$a_pass_selection
	 */
	public function setPassSelection($a_pass_selection)
	{
		// cleanup all read content if pass selection changes
		if (isset($this->pass_selection) && $this->pass_selection != $a_pass_selection)
		{
			$this->content = array();
			$this->validated = false;
		}
		$this->pass_selection = $a_pass_selection;
	}

	/**
	 * Preload cached content
	 * The content key is dependent from the consumer class
	 * @param string $a_consumer_class
	 * @param string $a_content_key_prefix
	 */
	public function preload($a_consumer_class, $a_content_key_prefix)
	{
		global $ilDB;

		$query = "SELECT content_key, content FROM etstat_cache"
			." WHERE test_id = ". $ilDB->quote($this->test_id, 'integer')
			." AND pass_selection = " . $ilDB->quote($this->pass_selection, 'text')
			." AND consumer_class = " . $ilDB->quote($a_consumer_class, 'text')
			." AND " . $ilDB->like('content_key', 'text', $a_content_key_prefix.'%', false);

		$result = $ilDB->query($query);
		while ($row = $ilDB->fetchAssoc($result))
		{
			$this->content[$a_consumer_class][$row['content_key']] = $row['content'];
		}
	}

	/**
	 * Read a cached content
	 * The content key is dependent from the consumer class
	 * @param string $a_consumer_class
	 * @param string $a_content_key
	 * @return string
	 */
	public function read($a_consumer_class, $a_content_key)
	{
		global $ilDB;

		$this->validate();

		if (!isset($this->content[$a_consumer_class][$a_content_key]))
		{
			$query = "SELECT content FROM etstat_cache"
				." WHERE test_id = ". $ilDB->quote($this->test_id, 'integer')
				." AND pass_selection = " . $ilDB->quote($this->pass_selection, 'text')
				." AND consumer_class = " . $ilDB->quote($a_consumer_class, 'text')
				." AND content_key = " . $ilDB->quote($a_content_key, 'text');

			$result = $ilDB->query($query);
			if ($row = $ilDB->fetchAssoc($result))
			{
				$this->content[$a_consumer_class][$a_content_key] = $row['content'];
			}
		}

		return $this->content[$a_consumer_class][$a_content_key];
	}


	/**
	 * Write a cached content
	 * The content key is dependent from the consumer class
	 * @param string $a_consumer_class
	 * @param string $a_content_key
	 * @param string $a_content
	 */
	public function write($a_consumer_class, $a_content_key, $a_content)
	{
		global $ilDB;

		$ilDB->replace('etstat_cache', array(
			'test_id' => array('integer', $this->test_id),
			'pass_selection' => array('text', $this->pass_selection),
			'consumer_class' => array('text', $a_consumer_class),
			'content_key' => array('text', $a_content_key)
		), array(
			'content' => array('clob', $a_content),
			'tstamp' => array('integer', time())
		));

		$this->content[$a_consumer_class][$a_content_key] = $a_content;
	}

	/**
	 * Check if the cache is still valid
	 * Delete the cache if it is not valid
	 */
	private function validate()
	{
		global $ilDB;

		if ($this->validated)
		{
			return;
		}

		$test_query = "SELECT max(r.tstamp) last_update FROM tst_test_result r"
			. " INNER JOIN tst_active a ON r.active_fi = a.active_id"
			. " WHERE a.test_fi = " . $ilDB->quote($this->test_id, 'integer');

		$test_result = $ilDB->query($test_query);
		$test_row = $ilDB->fetchAssoc($test_result);


		$cache_query = "SELECT min(tstamp) first_cache FROM etstat_cache"
			." WHERE test_id = ". $ilDB->quote($this->test_id, 'integer');
		$cache_result = $ilDB->query($cache_query);
		$cache_row = $ilDB->fetchAssoc($cache_result);

		if (empty($test_row) || (!empty($cache_row) && $cache_row['first_cache'] <= $test_row['last_update']))
		{
			$this->flush();
		}

		$this->validated = true;
	}

	/**
	 * Flush the cached values of a test
	 */
	public function flush()
	{
		global $ilDB;
		
		$ilDB->manipulate("DELETE FROM etstat_cache WHERE test_id=" . $ilDB->quote($this->test_id, 'integer'));
		$this->content = array();
	}

	/**
	 * Flush all cached values (e.g. after an update)
	 */
	public static function flushAll()
	{
		global $ilDB;
		$ilDB->manipulate("DELETE FROM etstat_cache");
	}
}