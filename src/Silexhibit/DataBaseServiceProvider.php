<?php

namespace Silexhibit;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class DataBaseServiceProvider implements ServiceProviderInterface {

  protected $dbal;
  protected $tbl;

  public function register(Container $app) {
    $this->tbl = $app['config']['db']['table_prefix'];
    $this->dbal = $app['db'];
    $app['database'] = $this;
  }

  public function selectExhibit($column, $value, $public = false) {
    $record = $this->selectPost($column, $value, $public);
    if (!empty($record)) {
      $record['exhibit'] = $this->selectExhibitMedia($record['id']);
    }
    return $record;
  }

  public function selectExhibitMedia($id) {
    $query = "SELECT *
      FROM {$this->tbl}media AS m
      WHERE m.media_ref_id = ?
        AND m.media_obj_type = 'exhibit'
      ORDER BY m.media_order ASC,
               m.media_id ASC";
    return $this->dbal->fetchAll($query, array($id));
  }

  const INDEX_CHRONOLOGICAL = 1;
  const INDEX_SECTIONAL = 2;

  public function selectIndex($type = self::INDEX_CHRONOLOGICAL, $public = false) {
    $query = "SELECT id, title, content, url,
      section, sec_desc, sec_disp, year, secid
      FROM {$this->tbl}objects as o, {$this->tbl}sections as s
      WHERE o.section_id = s.secid";
    if ($public) {
      $query .= " AND o.status = '1' AND o.hidden != '1'";
    }
    switch ($type) {
      case self::INDEX_CHRONOLOGICAL:
        $query .= " ORDER BY s.sec_ord ASC, o.year DESC, o.ord ASC";
        break;
      case self::INDEX_SECTIONAL:
        $query .= " ORDER BY s.sec_ord ASC, o.ord ASC";
        break;
      default: break;
    }
    $index = $this->dbal->fetchAll($query);
    return $index;
  }

  public function selectPost($column, $value, $public = false) {
    $query = "SELECT *
      FROM {$this->tbl}objects AS o,
           {$this->tbl}objects_prefs AS op
      WHERE o.$column = ?
      AND o.object = op.obj_ref_type";
    if ($public) {
      $query .= " AND o.status = '1'";
    }
    return $this->dbal->fetchAssoc($query, array($value));
  }

}
