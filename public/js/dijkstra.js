// Dijkstra Algorithm Implementation for GeoJSON network

class Graph {
    constructor() {
        this.nodes = new Map();
    }

    addNode(name, data = {}) {
        this.nodes.set(name, { edges: [], data: data });
    }

    addEdge(source, dest, weight) {
        if (!this.nodes.has(source) || !this.nodes.has(dest)) return;
        this.nodes.get(source).edges.push({ node: dest, weight: weight });
        // Assume undirected graph for road network
        this.nodes.get(dest).edges.push({ node: source, weight: weight });
    }

    clone() {
        let newGraph = new Graph();
        for (let [nodeName, nodeData] of this.nodes.entries()) {
            // Shallow clone edges and data
            newGraph.nodes.set(nodeName, {
                edges: [...nodeData.edges],
                data: { ...nodeData.data }
            });
        }
        return newGraph;
    }

    dijkstra(start, end) {
        let distances = new Map();
        let previous = new Map();
        let unvisited = new Set(this.nodes.keys());

        for (let node of this.nodes.keys()) {
            distances.set(node, Infinity);
            previous.set(node, null);
        }
        distances.set(start, 0);

        while (unvisited.size > 0) {
            let currNode = null;
            let minDistance = Infinity;
            for (let node of unvisited) {
                if (distances.get(node) < minDistance) {
                    minDistance = distances.get(node);
                    currNode = node;
                }
            }

            if (currNode === null || currNode === end) break;
            unvisited.delete(currNode);

            for (let edge of this.nodes.get(currNode).edges) {
                let alt = distances.get(currNode) + edge.weight;
                if (alt < distances.get(edge.node)) {
                    distances.set(edge.node, alt);
                    previous.set(edge.node, currNode);
                }
            }
        }

        let path = [];
        let curr = end;
        if (previous.get(curr) !== null || curr === start) {
            while (curr !== null) {
                path.unshift(curr);
                curr = previous.get(curr);
            }
        }
        return path;
    }

    removeEdge(source, dest) {
        if (this.nodes.has(source)) {
            let nodeData = this.nodes.get(source);
            nodeData.edges = nodeData.edges.filter(e => e.node !== dest);
        }
        if (this.nodes.has(dest)) {
            let nodeData = this.nodes.get(dest);
            nodeData.edges = nodeData.edges.filter(e => e.node !== source);
        }
    }

    alternativePath(start, end) {
        const primaryPath = this.dijkstra(start, end);
        if (primaryPath.length <= 2) return []; // Not enough edges to find a meaningful alternative

        // Try removing the middle edge to force a significant detour
        const midIndex = Math.floor(primaryPath.length / 2);
        const nodeA = primaryPath[midIndex - 1];
        const nodeB = primaryPath[midIndex];

        let tempGraph = this.clone();
        tempGraph.removeEdge(nodeA, nodeB);
        
        let altPath = tempGraph.dijkstra(start, end);
        
        // If disconnected, try removing the first edge instead
        if (altPath.length === 0 || altPath[altPath.length - 1] !== end) {
            let tempGraph2 = this.clone();
            tempGraph2.removeEdge(primaryPath[0], primaryPath[1]);
            altPath = tempGraph2.dijkstra(start, end);
            if (altPath.length === 0 || altPath[altPath.length - 1] !== end) return [];
        }
        
        // Ensure it's actually different (sometimes graphs have only 1 path)
        if (altPath.join(',') === primaryPath.join(',')) return [];
        
        return altPath;
    }
}

window.DijkstraGraph = Graph;
