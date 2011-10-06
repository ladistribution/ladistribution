<?php

class Ld_Model_Backend_Mongo
{

    public function __construct($id)
    {
        $m = new Mongo();
        $db = $m->ladistribution; // be more specific here
        $this->collection = $db->$id;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function getAll()
    {
        $cursor = $this->collection->find();
        $array = iterator_to_array($cursor);
        return $array;
    }

    public function create($params = array())
    {
        return $this->collection->insert($params);
    }

    public function read($query)
    {
        if (is_string($query)) {
            $query = array('_id' => new MongoId($query));
        }
        $object = $this->collection->findOne($query);
        return $object;
    }

    public function update($query, $params = array())
    {
        if (is_string($query)) {
            $query = array('_id' => new MongoId($query));
        }
        return $this->collection->update($query, $params);
    }

    public function delete($query)
    {
        if (is_string($query)) {
            $query = array('_id' => new MongoId($query));
        }
        return $this->collection->remove($query);
    }

}
