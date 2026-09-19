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
}

window.DijkstraGraph = Graph;
