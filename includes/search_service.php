<?php

class SearchService {
    private $db;
    
    public function __construct() {
        require_once 'database/config.php';
        $this->db = new Database();
    }
    
    /**
     * Search incidents with advanced filtering
     */
    public function searchIncidents($query = '', $filters = [], $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build base query
            $sql = "SELECT i.*, u.full_name as reporter_name, u.email as reporter_email 
                    FROM incidents i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Add search query
            if (!empty($query)) {
                $sql .= " AND (i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
                $searchTerm = "%{$query}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Add filters
            if (!empty($filters['severity'])) {
                $sql .= " AND i.severity = ?";
                $params[] = $filters['severity'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND i.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['category'])) {
                $sql .= " AND i.category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['location'])) {
                $sql .= " AND i.location LIKE ?";
                $params[] = "%{$filters['location']}%";
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND i.created_at >= ?";
                $params[] = $filters['date_from'] . ' 00:00:00';
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND i.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }
            
            // Add ordering and pagination
            $sql .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            // Execute search
            $results = $this->db->query($sql, $params);
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM incidents i WHERE 1=1";
            $countParams = [];
            
            if (!empty($query)) {
                $countSql .= " AND (i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
                $countParams[] = "%{$query}%";
                $countParams[] = "%{$query}%";
                $countParams[] = "%{$query}%";
            }
            
            if (!empty($filters['severity'])) {
                $countSql .= " AND i.severity = ?";
                $countParams[] = $filters['severity'];
            }
            
            if (!empty($filters['status'])) {
                $countSql .= " AND i.status = ?";
                $countParams[] = $filters['status'];
            }
            
            if (!empty($filters['category'])) {
                $countSql .= " AND i.category = ?";
                $countParams[] = $filters['category'];
            }
            
            if (!empty($filters['location'])) {
                $countSql .= " AND i.location LIKE ?";
                $countParams[] = "%{$filters['location']}%";
            }
            
            if (!empty($filters['date_from'])) {
                $countSql .= " AND i.created_at >= ?";
                $countParams[] = $filters['date_from'] . ' 00:00:00';
            }
            
            if (!empty($filters['date_to'])) {
                $countSql .= " AND i.created_at <= ?";
                $countParams[] = $filters['date_to'] . ' 23:59:59';
            }
            
            $countResult = $this->db->query($countSql, $countParams);
            $total = $countResult->fetch()['total'];
            
            return [
                'incidents' => $results->fetchAll(),
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return [
                'incidents' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'pages' => 0,
                'error' => 'Search failed. Please try again.'
            ];
        }
    }
    
    /**
     * Get search suggestions based on popular terms
     */
    public function getSearchSuggestions($query = '') {
        try {
            $suggestions = [];
            
            if (empty($query)) {
                // Return popular search terms
                $suggestions = [
                    'theft', 'assault', 'vandalism', 'fraud',
                    'douala', 'yaounde', 'buea', 'bamenda',
                    'high severity', 'resolved', 'pending'
                ];
            } else {
                // Get suggestions from incident titles and locations
                $sql = "SELECT DISTINCT title, location, category 
                        FROM incidents 
                        WHERE title LIKE ? OR location LIKE ? OR category LIKE ?
                        LIMIT 10";
                
                $searchTerm = "%{$query}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
                
                $results = $this->db->query($sql, $params);
                
                foreach ($results->fetchAll() as $row) {
                    $suggestions[] = $row['title'];
                    $suggestions[] = $row['location'];
                    $suggestions[] = $row['category'];
                }
                
                $suggestions = array_unique($suggestions);
                $suggestions = array_slice($suggestions, 0, 5);
            }
            
            return $suggestions;
            
        } catch (Exception $e) {
            error_log("Suggestions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get search statistics
     */
    public function getSearchStatistics() {
        try {
            $stats = [];
            
            // Total incidents
            $result = $this->db->query("SELECT COUNT(*) as total FROM incidents");
            $stats['total_incidents'] = $result->fetch()['total'];
            
            // By severity
            $result = $this->db->query("SELECT severity, COUNT(*) as count FROM incidents GROUP BY severity");
            $stats['by_severity'] = [];
            foreach ($result->fetchAll() as $row) {
                $stats['by_severity'][$row['severity']] = $row['count'];
            }
            
            // By status
            $result = $this->db->query("SELECT status, COUNT(*) as count FROM incidents GROUP BY status");
            $stats['by_status'] = [];
            foreach ($result->fetchAll() as $row) {
                $stats['by_status'][$row['status']] = $row['count'];
            }
            
            // By category
            $result = $this->db->query("SELECT category, COUNT(*) as count FROM incidents GROUP BY category");
            $stats['by_category'] = [];
            foreach ($result->fetchAll() as $row) {
                $stats['by_category'][$row['category']] = $row['count'];
            }
            
            // Recent activity
            $result = $this->db->query("SELECT COUNT(*) as count FROM incidents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stats['last_24_hours'] = $result->fetch()['count'];
            
            $result = $this->db->query("SELECT COUNT(*) as count FROM incidents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stats['last_7_days'] = $result->fetch()['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Statistics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get trending search terms
     */
    public function getTrendingSearches($limit = 10) {
        try {
            // This would typically come from a search_logs table
            // For now, return mock trending searches
            return [
                'theft', 'assault', 'vandalism', 'fraud',
                'douala', 'yaounde', 'high severity', 'resolved'
            ];
            
        } catch (Exception $e) {
            error_log("Trending searches error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log search query for analytics
     */
    public function logSearch($query, $filters = [], $results_count = 0) {
        try {
            // This would save to a search_logs table for analytics
            // For now, just log to error log
            $logData = [
                'query' => $query,
                'filters' => $filters,
                'results_count' => $results_count,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            
            error_log("Search logged: " . json_encode($logData));
            
        } catch (Exception $e) {
            error_log("Search log error: " . $e->getMessage());
        }
    }
    
    /**
     * Get advanced filter options
     */
    public function getFilterOptions() {
        try {
            $options = [];
            
            // Severity options
            $options['severity'] = ['high', 'medium', 'low'];
            
            // Status options
            $options['status'] = ['pending', 'investigating', 'resolved'];
            
            // Category options
            $result = $this->db->query("SELECT DISTINCT category FROM incidents ORDER BY category");
            $options['category'] = [];
            foreach ($results = $result->fetchAll() as $row) {
                $options['category'][] = $row['category'];
            }
            
            // Location options (top cities)
            $result = $this->db->query("SELECT DISTINCT location FROM incidents ORDER BY location LIMIT 20");
            $options['locations'] = [];
            foreach ($results = $result->fetchAll() as $row) {
                $options['locations'][] = $row['location'];
            }
            
            return $options;
            
        } catch (Exception $e) {
            error_log("Filter options error: " . $e->getMessage());
            return [];
        }
    }
}
?>
