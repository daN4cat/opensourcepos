<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Rewards class
 */

class Rewards extends CI_Model
{
	public function exists($rewards_id)
	{
		$this->db->from('sales_reward_points');
		$this->db->where('id', $rewards_id);

		return ($this->db->get()->num_rows() == 1);
	}

	/*
	Inserts or updates a rewards
	*/
	public function save(&$rewards_data, $rewards_id = FALSE)
	{
		if(!$rewards_id || !$this->exists($rewards_id))
		{
			if($this->db->insert('sales_reward_points', $rewards_data))
			{
				$rewards_data['id'] = $this->db->insert_id();

				return TRUE;
			}

			return FALSE;
		}

		$this->db->where('id', $rewards_id);

		return $this->db->update('sales_reward_points', $rewards_data);
	}
}
?>
