<?php
/**
 * Menu Model - Handles dynamic menus and menu items
 */
class Menu {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Get all menus
    public function getAllMenus() {
        $stmt = $this->db->query("SELECT * FROM menus ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    // Get menu by ID
    public function getMenu($id) {
        $stmt = $this->db->prepare("SELECT * FROM menus WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Create a new menu
    public function createMenu($name) {
        $stmt = $this->db->prepare("INSERT INTO menus (name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->db->lastInsertId();
    }

    // Delete a menu
    public function deleteMenu($id) {
        $stmt = $this->db->prepare("DELETE FROM menus WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Get menu items for a specific menu
    public function getMenuItems($menu_id) {
        $stmt = $this->db->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY display_order ASC");
        $stmt->execute([$menu_id]);
        return $stmt->fetchAll();
    }

    // Get assigned locations for a specific menu
    public function getMenuLocations($menu_id) {
        $stmt = $this->db->prepare("SELECT location FROM menu_locations WHERE menu_id = ?");
        $stmt->execute([$menu_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Save menu details, locations, and items
    public function saveMenu($menu_id, $name, $locations, $items) {
        try {
            $this->db->beginTransaction();

            // 1. Update menu name
            $stmt = $this->db->prepare("UPDATE menus SET name = ? WHERE id = ?");
            $stmt->execute([$name, $menu_id]);

            // 2. Update locations
            // First clear this menu's locations
            $stmt = $this->db->prepare("DELETE FROM menu_locations WHERE menu_id = ?");
            $stmt->execute([$menu_id]);
            
            // Assign new locations
            if (!empty($locations)) {
                foreach ($locations as $loc) {
                    // A location can only have ONE menu, so remove it if it belongs to another menu
                    $stmt = $this->db->prepare("DELETE FROM menu_locations WHERE location = ?");
                    $stmt->execute([$loc]);

                    // Insert the new location mapping
                    $stmt = $this->db->prepare("INSERT INTO menu_locations (location, menu_id) VALUES (?, ?)");
                    $stmt->execute([$loc, $menu_id]);
                }
            }

            // 3. Update items (Replace all items for simplicity)
            $stmt = $this->db->prepare("DELETE FROM menu_items WHERE menu_id = ?");
            $stmt->execute([$menu_id]);

            if (!empty($items)) {
                $order = 0;
                $stmt = $this->db->prepare("INSERT INTO menu_items (menu_id, type, type_id, title, url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($items as $item) {
                    $type = $item['type'] ?? 'custom';
                    $type_id = !empty($item['type_id']) ? (int)$item['type_id'] : null;
                    $title = $item['title'] ?? '';
                    $url = $item['url'] ?? '';
                    
                    $stmt->execute([$menu_id, $type, $type_id, $title, $url, $order]);
                    $order++;
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Save Menu Error: " . $e->getMessage());
            return false;
        }
    }

    // Frontend: Get menu items for a specific location
    public function getMenuByLocation($location) {
        $stmt = $this->db->prepare("
            SELECT mi.* 
            FROM menu_items mi
            JOIN menu_locations ml ON mi.menu_id = ml.menu_id
            WHERE ml.location = ?
            ORDER BY mi.display_order ASC
        ");
        $stmt->execute([$location]);
        $items = $stmt->fetchAll();

        // If items are of type 'category', fetch the slug from categories table
        if (!empty($items)) {
            $categoryModel = new Category();
            foreach ($items as &$item) {
                if ($item['type'] === 'category' && !empty($item['type_id'])) {
                    $cat = $categoryModel->getById($item['type_id']);
                    if ($cat) {
                        $item['url'] = SITE_URL . '/category.php?slug=' . $cat['slug'];
                        // If no custom title was provided, use category name
                        if (empty($item['title'])) {
                            $item['title'] = $cat['name'];
                        }
                    }
                }
            }
        }
        
        return $items;
    }
}
