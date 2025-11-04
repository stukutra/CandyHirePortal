<?php
/**
 * SubscriptionTier Model
 *
 * Handles subscription_tiers table operations
 */

class SubscriptionTier {
    private $db;

    public $id;
    public $name;
    public $slug;
    public $category;
    public $description;
    public $price;
    public $currency;
    public $billing_period;
    public $original_price;
    public $features;
    public $highlights;
    public $badge_text;
    public $badge_icon;
    public $is_featured;
    public $is_enabled;
    public $sort_order;
    public $metadata;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all tiers with optional filters
     */
    public function getAll($filters = []) {
        $query = "SELECT * FROM subscription_tiers WHERE 1=1";
        $params = [];

        if (isset($filters['is_enabled'])) {
            $query .= " AND is_enabled = :is_enabled";
            $params[':is_enabled'] = $filters['is_enabled'];
        }

        if (isset($filters['is_featured'])) {
            $query .= " AND is_featured = :is_featured";
            $params[':is_featured'] = $filters['is_featured'];
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $query .= " AND (name LIKE :search OR category LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY sort_order ASC, created_at DESC";

        $stmt = $this->db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON fields
        foreach ($rows as &$row) {
            $row['features'] = json_decode($row['features'], true);
            $row['highlights'] = json_decode($row['highlights'], true);
            $row['metadata'] = json_decode($row['metadata'], true);
            $row['is_featured'] = (bool) $row['is_featured'];
            $row['is_enabled'] = (bool) $row['is_enabled'];
            $row['price'] = (float) $row['price'];
            $row['original_price'] = $row['original_price'] ? (float) $row['original_price'] : null;
        }

        return $rows;
    }

    /**
     * Get tier by ID
     */
    public function findById($id) {
        $query = "SELECT * FROM subscription_tiers WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->mapFromRow($row);
            return true;
        }

        return false;
    }

    /**
     * Get tier by slug
     */
    public function findBySlug($slug) {
        $query = "SELECT * FROM subscription_tiers WHERE slug = :slug LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->mapFromRow($row);
            return true;
        }

        return false;
    }

    /**
     * Create new tier
     */
    public function create() {
        $query = "INSERT INTO subscription_tiers
            (name, slug, category, description, price, currency, billing_period,
             original_price, features, highlights, badge_text, badge_icon,
             is_featured, is_enabled, sort_order, metadata)
            VALUES
            (:name, :slug, :category, :description, :price, :currency, :billing_period,
             :original_price, :features, :highlights, :badge_text, :badge_icon,
             :is_featured, :is_enabled, :sort_order, :metadata)";

        $stmt = $this->db->prepare($query);

        // Encode JSON fields
        $features_json = json_encode($this->features);
        $highlights_json = json_encode($this->highlights);
        $metadata_json = json_encode($this->metadata);

        // Convert booleans to integers for MySQL
        $is_featured_int = $this->is_featured ? 1 : 0;
        $is_enabled_int = $this->is_enabled ? 1 : 0;

        // Bind parameters
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':currency', $this->currency);
        $stmt->bindParam(':billing_period', $this->billing_period);
        $stmt->bindParam(':original_price', $this->original_price);
        $stmt->bindParam(':features', $features_json);
        $stmt->bindParam(':highlights', $highlights_json);
        $stmt->bindParam(':badge_text', $this->badge_text);
        $stmt->bindParam(':badge_icon', $this->badge_icon);
        $stmt->bindParam(':is_featured', $is_featured_int, PDO::PARAM_INT);
        $stmt->bindParam(':is_enabled', $is_enabled_int, PDO::PARAM_INT);
        $stmt->bindParam(':sort_order', $this->sort_order);
        $stmt->bindParam(':metadata', $metadata_json);

        if ($stmt->execute()) {
            $this->id = $this->db->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Update tier
     */
    public function update() {
        $query = "UPDATE subscription_tiers SET
            name = :name,
            slug = :slug,
            category = :category,
            description = :description,
            price = :price,
            currency = :currency,
            billing_period = :billing_period,
            original_price = :original_price,
            features = :features,
            highlights = :highlights,
            badge_text = :badge_text,
            badge_icon = :badge_icon,
            is_featured = :is_featured,
            is_enabled = :is_enabled,
            sort_order = :sort_order,
            metadata = :metadata
            WHERE id = :id";

        $stmt = $this->db->prepare($query);

        // Encode JSON fields
        $features_json = json_encode($this->features);
        $highlights_json = json_encode($this->highlights);
        $metadata_json = json_encode($this->metadata);

        // Convert booleans to integers for MySQL
        $is_featured_int = $this->is_featured ? 1 : 0;
        $is_enabled_int = $this->is_enabled ? 1 : 0;

        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':currency', $this->currency);
        $stmt->bindParam(':billing_period', $this->billing_period);
        $stmt->bindParam(':original_price', $this->original_price);
        $stmt->bindParam(':features', $features_json);
        $stmt->bindParam(':highlights', $highlights_json);
        $stmt->bindParam(':badge_text', $this->badge_text);
        $stmt->bindParam(':badge_icon', $this->badge_icon);
        $stmt->bindParam(':is_featured', $is_featured_int, PDO::PARAM_INT);
        $stmt->bindParam(':is_enabled', $is_enabled_int, PDO::PARAM_INT);
        $stmt->bindParam(':sort_order', $this->sort_order);
        $stmt->bindParam(':metadata', $metadata_json);

        return $stmt->execute();
    }

    /**
     * Delete tier
     */
    public function delete() {
        $query = "DELETE FROM subscription_tiers WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    /**
     * Get total count with filters
     */
    public function getCount($filters = []) {
        $query = "SELECT COUNT(*) as total FROM subscription_tiers WHERE 1=1";
        $params = [];

        if (isset($filters['is_enabled'])) {
            $query .= " AND is_enabled = :is_enabled";
            $params[':is_enabled'] = $filters['is_enabled'];
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $query .= " AND (name LIKE :search OR category LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    /**
     * Map database row to object properties
     */
    private function mapFromRow($row) {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->slug = $row['slug'];
        $this->category = $row['category'];
        $this->description = $row['description'];
        $this->price = (float) $row['price'];
        $this->currency = $row['currency'];
        $this->billing_period = $row['billing_period'];
        $this->original_price = $row['original_price'] ? (float) $row['original_price'] : null;
        $this->features = json_decode($row['features'], true);
        $this->highlights = json_decode($row['highlights'], true);
        $this->badge_text = $row['badge_text'];
        $this->badge_icon = $row['badge_icon'];
        $this->is_featured = (bool) $row['is_featured'];
        $this->is_enabled = (bool) $row['is_enabled'];
        $this->sort_order = (int) $row['sort_order'];
        $this->metadata = json_decode($row['metadata'], true);
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    /**
     * Convert object to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period,
            'original_price' => $this->original_price,
            'features' => $this->features,
            'highlights' => $this->highlights,
            'badge_text' => $this->badge_text,
            'badge_icon' => $this->badge_icon,
            'is_featured' => $this->is_featured,
            'is_enabled' => $this->is_enabled,
            'sort_order' => $this->sort_order,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
