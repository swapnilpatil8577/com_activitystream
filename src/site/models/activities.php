<?php
/**
 * @version    SVN: <svn_id>
 * @package    ActivityStream
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// No direct access to this file
defined('_JEXEC') or die;

/**
 * ActivityStreamList Model
 *
 * @since  0.0.1
 */
class ActivityStreamModelActivities extends JModelList
{
	protected $activityStreamActivitiesHelper;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   0.0.1
	 */
	public function __construct($config = array())
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_activitystream/helpers/activities.php';

		$this->activityStreamActivitiesHelper = new ActivityStreamHelperActivities;

		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'state',
				'access',
				'type',
				'actor_id',
				'object_id',
				'target_id',
				'created_date',
				'updated_date'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return      string  An SQL query
	 */
	protected function getListQuery()
	{
		// Initialize variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select($this->getState('list.select', '*'))
			->from($db->quoteName('#__tj_activities'));

		// Filter: like / search
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$like = $db->quote('%' . $search . '%');
			$query->where('type LIKE ' . $like);
		}

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('state = ' . (int) $published);
		}

		$type = $this->getState('type');
		$from_date = $this->getState('from_date');
		$limit = $this->getState('list.limit');
		$start = $this->getState('list.start');

		$result_arr = array();

		// Return result related to specified activity type
		if (!empty($type) && $type != 'all')
		{
			$type = $this->activityStreamActivitiesHelper->buildActivityFilterQuery($type);
			$query->where($db->quoteName('type') . ' IN (' . $type . ')');
		}

		// Get all filters
		$filters = $this->get('filter_fields');

		foreach ($filters as $filter)
		{
			$filterValue = $this->getState($filter);

			if (!empty($filterValue) && $filter != 'type')
			{
				$filterValue = $this->activityStreamActivitiesHelper->buildActivityFilterQuery($filterValue);
				$query->where($db->quoteName($filter) . ' IN (' . $filterValue . ')');
			}
		}

		// Return results from specified date
		if (!empty($from_date))
		{
			$query->where($db->quoteName('created_date') . ' >= ' . $db->quote($from_date));
		}

		if ($limit != 0)
		{
			$query->setLimit($limit, $start);
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'created_date');
		$orderDirn = $this->state->get('list.direction', 'desc');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}

	/**
	 * Method to get a list of activities.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   0.0.1
	 */
	public function getItems()
	{
		$items = parent::getItems();

		$activities = array();

		if (!empty($items))
		{
			foreach ($items as $k => $item)
			{
				// Get date in local time zone
				$item->created_date = JHtml::date($item->created_date, 'Y-m-d h:i:s');
				$item->updated_date = JHtml::date($item->updated_date, 'Y-m-d h:i:s');
				$item->root = JUri::root();

				// Get extra date info
				$items[$k]->created_day = date_format(date_create($item->created_date), "D");
				$items[$k]->created_date_month = date_format(date_create($item->created_date), "d, M");

				// Convert item data into array
				$itemArray = (array) $item;

				// Convet all the json data to array
				$itemArray = $this->activityStreamActivitiesHelper->json_to_array($itemArray, true);

				foreach ($itemArray as $key => $itemSubArray)
				{
					if (is_array($itemSubArray))
					{
						// Convet all the json data to array
						$itemArray[$key] = $this->activityStreamActivitiesHelper->json_to_array($itemSubArray, true);
					}
				}

				// Create array of item objects
				$activities[] = (object) $itemArray;
			}
		}

		return $activities;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('a.id', 'asc');
	}
}
